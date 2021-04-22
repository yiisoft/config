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

use function array_key_exists;
use function dirname;
use function in_array;

/**
 * ComposerEventHandler responds to composer event. In the package, its job is to copy configs from packages to
 * the application and to prepare a merge plan that is later used by {@see Config}.
 */
final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface
{
    private ?Composer $composer = null;

    /**
     * @var string[] Pretty names of updated packages.
     */
    private array $updatedPackagesPrettyNames = [];

    /**
     * @var string[] Names of removed packages.
     */
    private array $removals = [];

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
            $this->processConfigs($event->getComposer(), $event->getIO());
        }
    }

    public function onPostUpdateCommandDump(Event $event): void
    {
        $this->processConfigs($event->getComposer(), $event->getIO());
    }

    private function processConfigs(Composer $composer, IOInterface $io): void
    {
        // Register autoloader.
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $rootPath = $this->getRootPath();
        $rootPackage = $composer->getPackage();
        $rootConfig = $this->getPluginConfig($rootPackage);
        $options = new Options($rootPackage->getExtra());
        $configFiles = new ConfigFiles($io, $rootPath, $options->outputDirectory());
        $this->ensureDirectoryExists($rootPath . $options->outputDirectory());

        $forceCheck = $options->forceCheck() ||
            in_array(Options::CONFIG_PACKAGE_PRETTY_NAME, $this->updatedPackagesPrettyNames, true);
        $packagesForCheck = $forceCheck ? [] : $this->updatedPackagesPrettyNames;

        foreach ($this->removals as $packageName) {
            $this->markPackageConfigAsRemoved($packageName, $rootPath . $options->outputDirectory());
        }

        $mergePlan = [];

        foreach ((new PackagesListBuilder($composer))->build() as $package) {
            $pluginConfig = $this->getPluginConfig($package);
            $pluginOptions = new Options($package->getExtra());

            foreach ($pluginConfig as $group => $files) {
                $files = (array) $files;

                foreach ($files as $file) {
                    $isOptional = false;

                    if (ConfigFiles::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    // Do not copy variables.
                    if (ConfigFiles::isVariable($file)) {
                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    $checkFileOnUpdate = $forceCheck || in_array($package->getPrettyName(), $packagesForCheck, true);
                    $source = $this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/' . $file;

                    if (ConfigFiles::containsWildcard($file)) {
                        $matches = glob($source);
                        if ($isOptional && $matches === []) {
                            continue;
                        }

                        if ($checkFileOnUpdate) {
                            foreach ($matches as $match) {
                                $relativePath = str_replace($this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/', '', $match);
                                $configFiles->updateConfiguration($match, $package->getPrettyName() . '/' . $relativePath);
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
                        $configFiles->updateConfiguration(
                            $source,
                            $package->getPrettyName() . '/' . $file,
                            $options->silentOverride(),
                        );
                    }

                    $mergePlan[$group][$package->getPrettyName()][] = $file;
                }
            }
        }

        // Append root package config.
        foreach ($rootConfig as $group => $files) {
            $files = array_map(function ($file) use ($options) {
                if (ConfigFiles::isVariable($file)) {
                    return $file;
                }

                $isOptional = ConfigFiles::isOptional($file);
                if ($isOptional) {
                    $file = substr($file, 1);
                }

                $result = $isOptional ? '?' : '';
                if ($options->sourceDirectory() !== '/') {
                    $result .= ltrim($options->sourceDirectory(), '/') . '/';
                }
                return $result . $file;
            }, (array) $files);

            $mergePlan[$group] = ['/' => $files] + (array_key_exists($group, $mergePlan) ? $mergePlan[$group] : []);
        }

        $configFiles->updateMergePlaneAndOutputResult($mergePlan);
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
