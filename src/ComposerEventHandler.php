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
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yiisoft\VarDumper\VarDumper;

use function count;
use function dirname;
use function in_array;

/**
 * ComposerEventHandler responds to composer event. In the package, its job is to copy configs from packages to
 * the application and to prepare a merge plan that is later used by {@see Config}.
 */
final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface
{
    private const MERGE_PLAN_FILENAME = 'merge_plan.php';
    private const DEFAULT_OUTPUT_PATH = 'config/packages';
    private const DIST_DIRECTORY = 'dist';

    private ?Composer $composer = null;

    /**
     * @var PackageInterface[] Updated packages.
     */
    private array $updatedPackages = [];

    /**
     * @var string[] Names of removed packages.
     */
    private array $removals = [];

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostInstall',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
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
            $this->updatedPackages[] = $operation->getPackage();
        }
    }

    public function onPostUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation) {
            $this->updatedPackages[] = $operation->getTargetPackage();
        }
    }

    public function onPostUninstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $this->removals[] = $operation->getPackage()->getPrettyName();
        }
    }

    public function onPostAutoloadDump(Event $event): void
    {
        // Register autoloader.
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        $composer = $event->getComposer();
        $rootPackage = $composer->getPackage();
        $rootConfig = $this->getPluginConfig($rootPackage);
        $silentOverride = (bool)($rootConfig['silentOverride'] ?? false);
        $outputDirectory = $this->getPluginOutputDirectory($rootPackage);
        $this->ensureDirectoryExists($outputDirectory);

        $allPackages = array_filter(
            $composer->getRepositoryManager()->getLocalRepository()->getPackages(),
            static fn ($package) => $package instanceof CompletePackage
        );
        $packagesForCheck = array_map(
            static fn (PackageInterface $package) => $package->getPrettyName(),
            $this->updatedPackages
        );

        foreach ($this->removals as $packageName) {
            $this->markPackageConfigAsRemoved($packageName, $outputDirectory);
        }

        $mergePlan = [];

        foreach ($allPackages as $package) {
            $pluginConfig = $this->getPluginConfig($package);
            foreach ($pluginConfig as $group => $files) {
                $files = (array)$files;
                foreach ($files as $file) {
                    /** @var string $file */
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

                    $source = $this->getPackagePath($package) . '/' . $file;

                    if ($this->containsWildcard($file)) {
                        $matches = glob($source);
                        if ($isOptional && $matches === []) {
                            continue;
                        }

                        if (in_array($package->getPrettyName(), $packagesForCheck, true)) {
                            foreach ($matches as $match) {
                                $relativePath = str_replace($this->getPackagePath($package) . '/', '', $match);
                                $this->updateFile($match, $outputDirectory . '/' . $package->getPrettyName() . '/' . $relativePath);
                            }
                        }

                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($source)) {
                        // Skip it in both copying and final config.
                        continue;
                    }

                    if (in_array($package->getPrettyName(), $packagesForCheck, true)) {
                        $destination = $outputDirectory . '/' . $package->getPrettyName() . '/' . $file;
                        $this->updateFile($source, $destination, $silentOverride);
                    }

                    $mergePlan[$group][$package->getPrettyName()][] = $file;
                }
            }
        }

        // Append root package config.
        foreach ($rootConfig as $group => $files) {
            $mergePlan[$group]['/'] = (array)$files;
        }

        // Reverse package order in groups.
        foreach ($mergePlan as $group => $files) {
            $mergePlan[$group] = array_reverse($files, true);
        }

        $packageOptions = $outputDirectory . '/' . self::MERGE_PLAN_FILENAME;
        file_put_contents($packageOptions, "<?php\n\ndeclare(strict_types=1);\n\n// Do not edit. Content will be replaced.\nreturn " . VarDumper::create($mergePlan)->export(true) . ";\n");
    }

    private function updateFile(string $source, string $destination, bool $silentOverride = false): void
    {
        $distDestinationPath = dirname($destination) . '/' . self::DIST_DIRECTORY;
        $distFilename = $distDestinationPath . '/' . basename($destination);

        $fs = new Filesystem();

        if (!file_exists($destination)) {
            // First install config
            $fs->ensureDirectoryExists(dirname($destination));
            $fs->copy($source, $destination);
        } else {
            // Update config
            $sourceContent = file_get_contents($source);
            $destinationContent = file_get_contents($destination);
            $distContent = file_exists($distFilename) ? file_get_contents($distFilename) : '';

            if ($silentOverride && $destinationContent === $distContent) {
                // Dist file equals with installed config. Installing with overwrite - silently.
                $fs->copy($source, $destination);
            } elseif ($sourceContent !== $distContent) {
                // Dist file changed and installed config changed by user.
                $output = new ConsoleOutput();
                $output->writeln("<bg=magenta;fg=white>Config file has been changed. Please review \"{$destination}\" and change it according with .dist file.</>");
            }
        }

        $fs->ensureDirectoryExists($distDestinationPath);
        $fs->copy($source, $distFilename);
    }

    /**
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPluginConfig(PackageInterface $package): array
    {
        return $package->getExtra()['config-plugin'] ?? [];
    }

    /**
     * @psalm-return string
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPluginOutputDirectory(PackageInterface $package): string
    {
        return $this->getRootPath() . '/' . (string)($package->getExtra()['config-plugin-output-dir'] ?? self::DEFAULT_OUTPUT_PATH);
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
