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

final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    /**
     * Returns list of events the plugin is subscribed to.
     *
     * @return array list of events
     */
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
        $this->io = $io;
    }


    public function onPostUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation) {
            $package = $operation->getPackage();
            if ($package instanceof PackageInterface) {
                echo $package->getPrettyName() . "\n";
                // TODO: mark config file for smart update
            }
        }
    }

    public function onPostUninstall(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
            echo "removed " . $package->getPrettyName() . "\n";

            // TODO: remove package config
        }
    }

    public function onPostAutoloadDump(Event $event): void
    {
        // Register autoloader.
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        $composer = $event->getComposer();
        $rootPackage = $composer->getPackage();
        $appConfigs = $this->getRootPath() . '/config/packages';
        $fs = new Filesystem();
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();

        $config = [];

        foreach ($packages as $package) {
            if (!$package instanceof CompletePackage) {
                continue;
            }

            $pluginConfig = $package->getExtra()['config-plugin'] ?? [];
            if ($pluginConfig === []) {
                continue;
            }

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
                        $config[$group][$package->getPrettyName()][] = $file;
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

                            $destination = $appConfigs . '/' . $package->getPrettyName() . '/' . $relativePath;

                            if (!file_exists($destination)) {
                                $fs->ensureDirectoryExists(dirname($destination));
                                $fs->copy($match, $destination);
                            }
                        }

                        $config[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($source)) {
                        // skip it in both copying and final config
                        continue;
                    }

                    $destination = $appConfigs . '/' . $package->getPrettyName() . '/' . $file;

                    if (!file_exists($destination)) {
                        $fs->ensureDirectoryExists(dirname($destination));
                        $fs->copy($source, $destination);
                    }

                    $config[$group][$package->getPrettyName()][] = $file;
                }
            }
        }

        // append root package config
        $rootConfig = $rootPackage->getExtra()['config-plugin'] ?? [];
        foreach ($rootConfig as $group => $files) {
            $config[$group]['/'] = (array)$files;
        }

        // reverse package order in groups
        foreach ($config as $group => $files) {
            $config[$group] = array_reverse($files, true);
        }

        $packageOptions = $appConfigs . '/package_options.php';
        file_put_contents($packageOptions, "<?php\n\ndeclare(strict_types=1);\n\n// Do not edit. Content will be replaced.\nreturn " . VarDumper::create($config)->export(true) . ";\n");
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

    private function getRootPath(): string
    {
        return realpath(dirname(Factory::getComposerFile()));
    }

    private function getPackagePath(PackageInterface $package): string
    {
        $installationManager = $this->composer->getInstallationManager();
        return $installationManager->getInstallPath($package);
    }
}
