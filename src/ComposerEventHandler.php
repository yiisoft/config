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
use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

use function array_key_exists;
use function array_map;
use function dirname;
use function file_exists;
use function glob;
use function in_array;
use function ltrim;
use function realpath;
use function str_replace;
use function substr;

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
    private array $removedPackages = [];

    private bool $runOnCreateProject = false;
    private bool $runOnAutoloadDump = false;

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
            PluginEvents::PRE_COMMAND_RUN => 'onPreCommandRun',
            PluginEvents::COMMAND => 'onCommand',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
            ScriptEvents::POST_INSTALL_CMD => 'onPostUpdateCommandDump',
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
            $this->removedPackages[] = $operation->getPackage()->getPrettyName();
        }
    }
    
    public function onPreCommandRun(PreCommandRunEvent $event): void
    {
        if ($event->getCommand() === 'create-project') {
            $this->runOnCreateProject = true;
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

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    private function processConfigs(Composer $composer, IOInterface $io): void
    {
        // Register autoloader.
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $rootPackage = $composer->getPackage();
        $rootConfig = $this->getPluginConfig($rootPackage);
        $options = new Options($rootPackage->getExtra());

        $forceCheck = $options->forceCheck() ||
            in_array(Options::CONFIG_PACKAGE_PRETTY_NAME, $this->updatedPackagesPrettyNames, true);
        $packagesForCheck = $forceCheck ? [] : $this->updatedPackagesPrettyNames;

        $mergePlan = [];
        $configFiles = [];

        foreach ((new PackagesListBuilder($composer))->build() as $package) {
            $pluginConfig = $this->getPluginConfig($package);
            $pluginOptions = new Options($package->getExtra());

            foreach ($pluginConfig as $group => $files) {
                $files = (array) $files;

                foreach ($files as $file) {
                    $isOptional = false;

                    if (Options::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    // Do not copy variables.
                    if (Options::isVariable($file)) {
                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    $checkFileOnUpdate = $forceCheck || in_array($package->getPrettyName(), $packagesForCheck, true);
                    $sourceFilePath = $this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/' . $file;

                    if (Options::containsWildcard($file)) {
                        $matches = glob($sourceFilePath);

                        if ($isOptional && $matches === []) {
                            continue;
                        }

                        if ($checkFileOnUpdate) {
                            foreach ($matches as $match) {
                                $relativePath = str_replace($this->getPackagePath($package) . $pluginOptions->sourceDirectory() . '/', '', $match);
                                $configFiles[] = new ConfigFile($match, $package->getPrettyName() . '/' . $relativePath);
                            }
                        }

                        $mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($sourceFilePath)) {
                        // Skip it in both copying and final config.
                        continue;
                    }

                    if ($checkFileOnUpdate) {
                        $configFiles[] = new ConfigFile(
                            $sourceFilePath,
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
            $files = array_map(static function ($file) use ($options) {
                if (Options::isVariable($file)) {
                    return $file;
                }

                $isOptional = Options::isOptional($file);
                $result = $isOptional ? '?' : '';

                if ($isOptional) {
                    $file = substr($file, 1);
                }

                if ($options->sourceDirectory() !== '/') {
                    $result .= ltrim($options->sourceDirectory(), '/') . '/';
                }

                return $result . $file;
            }, (array) $files);

            $mergePlan[$group] = ['/' => $files] + (array_key_exists($group, $mergePlan) ? $mergePlan[$group] : []);
        }

        $configFileHandler = new ConfigFileHandler($io, $this->getRootPath(), $options->outputDirectory());

        if ($this->runOnCreateProject) {
            $configFileHandler->handleAfterCreateProject($configFiles, $mergePlan);
            return;
        }

        $configFileHandler->handle($configFiles, $this->removedPackages, $mergePlan);
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

    /**
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPluginConfig(PackageInterface $package): array
    {
        return $package->getExtra()['config-plugin'] ?? [];
    }
}
