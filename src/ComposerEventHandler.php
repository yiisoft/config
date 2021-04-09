<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yiisoft\VarDumper\VarDumper;

use function array_key_exists;
use function dirname;
use function in_array;

/**
 * ComposerEventHandler responds to composer event. In the package, its job is to copy configs from packages to
 * the application and to prepare a merge plan that is later used by {@see Config}.
 */
final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface
{
    private const CONFIG_PACKAGE_PRETTY_NAME = 'yiisoft/config';
    private const MERGE_PLAN_FILENAME = 'merge_plan.php';
    private const DIST_DIRECTORY = 'dist';

    private ?Composer $composer = null;

    /**
     * @var string[] Pretty names of updated packages.
     */
    private array $updatedPackagesPrettyNames = [];

    /**
     * @var string[] Names of removed packages.
     */
    private array $removals = [];

    /**
     * @var string[]
     */
    private array $newConfigFiles = [];

    /**
     * @var string[]
     */
    private array $updatedConfigFiles = [];

    private bool $runOnAutoloadDump = false;

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostInstall',
            PluginEvents::COMMAND => 'onCommand',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCommandDump',
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }

    public function onPostInstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $this->updatedPackagesPrettyNames[] = $operation->getPackage()->getPrettyName();
        }
    }

    public function onPostUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation) {
            $this->updatedPackagesPrettyNames[] = $operation->getTargetPackage()->getPrettyName();
        }
    }

    public function onPostUninstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $this->removals[] = $operation->getPackage()->getPrettyName();
        }
    }

    public function onCommand(CommandEvent $event): void
    {
        if ($event->getCommandName() === 'dump-autoload') {
            $this->runOnAutoloadDump = true;
        }
    }

    public function onPostAutoloadDump(Event $event): void
    {
        if ($this->runOnAutoloadDump) {
            $this->processConfigs($event->getComposer());
        }
    }

    public function onPostUpdateCommandDump(Event $event): void
    {
        $this->processConfigs($event->getComposer());
    }

    private function processConfigs(Composer $composer): void
    {
        // Register autoloader.
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $rootPackage = $composer->getPackage();
        $rootConfig = $this->getPluginConfig($rootPackage);
        $options = new Options($rootPackage->getExtra());

        $forceCheck = $options->forceCheck() ||
            in_array(self::CONFIG_PACKAGE_PRETTY_NAME, $this->updatedPackagesPrettyNames, true);

        $rootPath = $this->getRootPath();
        $outputDirectory = $rootPath . $options->outputDirectory();
        $this->ensureDirectoryExists($outputDirectory);

        $allPackages = (new PackagesListBuilder($composer))->build();
        $packagesForCheck = $forceCheck ? [] : $this->updatedPackagesPrettyNames;

        foreach ($this->removals as $packageName) {
            $this->markPackageConfigAsRemoved($packageName, $outputDirectory);
        }

        $mergePlan = [];

        foreach ($allPackages as $package) {
            $pluginConfig = $this->getPluginConfig($package);
            $pluginOptions = new Options($package->getExtra());
            foreach ($pluginConfig as $group => $files) {
                $files = (array)$files;
                foreach ($files as $file) {
                    $isOptional = false;
                    if ($this->isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    // Do not copy variables.
                    if ($this->isVariable($file)) {
                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    $checkFileOnUpdate = $forceCheck || in_array($package->getPrettyName(), $packagesForCheck, true);

                    $source = $this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/' . $file;

                    if ($this->containsWildcard($file)) {
                        $matches = glob($source);
                        if ($isOptional && $matches === []) {
                            continue;
                        }

                        if ($checkFileOnUpdate) {
                            foreach ($matches as $match) {
                                $relativePath = str_replace($this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/', '', $match);
                                $this->updateFile(
                                    $match,
                                    $rootPath,
                                    $options->outputDirectory() . '/' . $package->getPrettyName() . '/' . $relativePath
                                );
                            }
                        }

                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($source)) {
                        // Skip it in both copying and final config.
                        continue;
                    }

                    if ($checkFileOnUpdate) {
                        $this->updateFile(
                            $source,
                            $rootPath,
                            $options->outputDirectory() . '/' . $package->getPrettyName() . '/' . $file,
                            $options->silentOverride()
                        );
                    }

                    $mergePlan[$group][$package->getPrettyName()][] = $file;
                }
            }
        }

        // Append root package config.
        foreach ($rootConfig as $group => $files) {
            $files = array_map(
                function ($file) use ($options) {
                    if ($this->isVariable($file)) {
                        return $file;
                    }

                    $isOptional = $this->isOptional($file);
                    if ($isOptional) {
                        $file = substr($file, 1);
                    }

                    $result = $isOptional ? '?' : '';
                    if ($options->sourceDirectory() !== '/') {
                        $result .= ltrim($options->sourceDirectory(), '/') . '/';
                    }
                    return $result . $file;
                },
                (array)$files
            );
            $mergePlan[$group] = ['/' => $files] +
                (array_key_exists($group, $mergePlan) ? $mergePlan[$group] : []);
        }

        // Sort groups by alphabetical
        ksort($mergePlan);

        $this->makeMergePlanFile($outputDirectory . '/' . self::MERGE_PLAN_FILENAME, $mergePlan);

        $this->outputMessages();
    }

    private function makeMergePlanFile(string $file, array $mergePlan): void
    {
        $oldContent = file_exists($file) ? file_get_contents($file) : '';

        $content = '<?php' .
            "\n\n" .
            'declare(strict_types=1);' .
            "\n\n" .
            '// Do not edit. Content will be replaced.' .
            "\n" .
            'return ' . VarDumper::create($mergePlan)->export(true) . ';' .
            "\n";

        if (!$this->equalIgnoringLineEndings($oldContent, $content)) {
            file_put_contents($file, $content);
        }
    }

    private function outputMessages(): void
    {
        $message = [];

        if ($this->newConfigFiles) {
            $message[] = 'Config files has been added:';
            foreach ($this->newConfigFiles as $file) {
                $message[] = ' - ' . $file;
            }
        }

        if ($this->updatedConfigFiles) {
            if ($message) {
                $message[] = '';
            }
            $message[] = 'Config files has been changed:';
            foreach ($this->updatedConfigFiles as $file) {
                $message[] = ' - ' . $file;
            }
            $message[] = 'Please review files above and change it according with dist files.';
        }

        if ($message) {
            (new ConsoleOutput())->writeln('<bg=magenta;fg=white>' . implode("\n", $message) . '</>');
        }
    }

    private function updateFile(
        string $source,
        string $destinationDirectory,
        string $destinationFile,
        bool $silentOverride = false
    ): void {
        $destination = $destinationDirectory . $destinationFile;

        $distDestinationPath = dirname($destination) . '/' . self::DIST_DIRECTORY;
        $distFilename = $distDestinationPath . '/' . basename($destination);

        $fs = new Filesystem();

        if (!file_exists($destination)) {
            // First install config
            $fs->ensureDirectoryExists(dirname($destination));
            $fs->copy($source, $destination);
            $this->newConfigFiles[] = ltrim($destinationFile, '/');
            $configChanged = true;
        } else {
            // Update config
            $sourceContent = file_get_contents($source);
            $destinationContent = file_get_contents($destination);
            $distContent = file_exists($distFilename) ? file_get_contents($distFilename) : '';

            $configChanged = !$this->equalIgnoringLineEndings($sourceContent, $distContent);
            if ($configChanged) {
                if ($silentOverride && $this->equalIgnoringLineEndings($destinationContent, $distContent)) {
                    // Dist file equals with installed config. Installing with overwrite - silently.
                    $fs->copy($source, $destination);
                } else {
                    // Dist file changed and installed config changed by user.
                    $this->updatedConfigFiles[] = ltrim($destinationFile, '/');
                }
            }
        }

        if ($configChanged) {
            $fs->ensureDirectoryExists($distDestinationPath);
            $fs->copy($source, $distFilename);
        }
    }

    private function equalIgnoringLineEndings(string $a, string $b): bool
    {
        return $this->normalizeLineEndings($a) === $this->normalizeLineEndings($b);
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }

    /**
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPluginConfig(PackageInterface $package): array
    {
        return $package->getExtra()['config-plugin'] ?? [];
    }

    private function ensureDirectoryExists(string $directoryPath): void
    {
        $fs = new Filesystem();
        $fs->ensureDirectoryExists($directoryPath);
    }

    /**
     * Remove application config for the package name specified.
     *
     * @param string $package Package name to remove application config for.
     * @param string $outputDirectory
     */
    private function markPackageConfigAsRemoved(string $package, string $outputDirectory): void
    {
        $packageConfigPath = $outputDirectory . '/' . $package;
        if (!file_exists($packageConfigPath)) {
            return;
        }

        $removedPackageConfigPath = $packageConfigPath . '.removed';

        $fs = new Filesystem();
        if (file_exists($removedPackageConfigPath)) {
            $fs->removeDirectory($removedPackageConfigPath);
        }
        $fs->rename($packageConfigPath, $removedPackageConfigPath);
    }

    private function containsWildcard(string $file): bool
    {
        return strpos($file, '*') !== false;
    }

    private function isOptional(string $file): bool
    {
        return strpos($file, '?') === 0;
    }

    private function isVariable(string $file): bool
    {
        return strpos($file, '$') === 0;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    /**
     * @return string Path to directory containing composer.json.
     * @psalm-suppress MixedArgument
     */
    private function getRootPath(): string
    {
        return realpath(dirname(Factory::getComposerFile()));
    }

    private function getPackagePath(PackageInterface $package): string
    {
        /** @psalm-suppress PossiblyNullReference */
        return $this->composer->getInstallationManager()->getInstallPath($package);
    }
}
