<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
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
use Yiisoft\VarDumper\VarDumper;
use function dirname;

/**
 * ComposerEventHandler responds to composer event. In the package, its job is to copy configs from packages to
 * the application and to prepare a merge plan that is later used by {@see Config}.
 */
final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface
{
    private const MERGE_PLAN_FILENAME = 'merge_plan.php';
    private const DEFAULT_OUTPUT_PATH = '/config/packages';
    private const DEFAULT_CONFIG_SOURCE_PATH = '/config';

    private ?Composer $composer = null;

    /**
     * @var string[] Names of updated packages.
     */
    private array $updates = [];

    /**
     * @var string[] Names of removed packages.
     */
    private array $removals = [];

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }


    public function onPostUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation) {
            $this->updates[] = $operation->getTargetPackage()->getPrettyName();
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
        $outputDirectory = $this->getPluginOutputDirectory($rootPackage);
        $this->createIfNotExistsDirectory($outputDirectory);

        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();

        foreach ($this->removals as $packageName) {
            $this->removePackageConfig($packageName);
        }

        $mergePlan = [];

        foreach ($packages as $package) {
            if (!$package instanceof CompletePackage) {
                continue;
            }

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

                        foreach ($matches as $match) {
                            $relativePath = str_replace($this->getPackagePath($package) . '/', '', $match);
                            $destination = $outputDirectory . '/' . $package->getPrettyName() . '/' . $relativePath;

                            $this->updateFile($match, $destination);
                        }

                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($source)) {
                        // Skip it in both copying and final config.
                        continue;
                    }

                    $destination = $outputDirectory . '/' . $package->getPrettyName() . '/' . $file;

                    $this->updateFile($source, $destination);

                    $mergePlan[$group][$package->getPrettyName()][] = $file;
                }
            }
        }

        // Append root package config.
        $rootConfig = $this->getPluginConfig($rootPackage);
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

    private function updateFile(string $source, string $destination): void
    {
        // TODO: if update happened, do merge here
        if (!file_exists($destination)) {
            $fs = new Filesystem();
            $fs->ensureDirectoryExists(dirname($destination));
            $fs->copy($source, $destination);
        }
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
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPluginOutputDirectory(PackageInterface $package): string
    {
        return $this->getRootPath() . (string)($package->getExtra()['config-plugin']['config-plugin-output-dir'] ?? self::DEFAULT_OUTPUT_PATH);
    }

    private function createIfNotExistsDirectory(string $directoryPath)
    {
        $fs = new Filesystem();
        $fs->ensureDirectoryExists($directoryPath);
    }

    /**
     * Remove application config for the package name specified.
     *
     * @param string $package Package name to remove application config for.
     */
    private function removePackageConfig(string $package): void
    {
        // TODO: implement
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
