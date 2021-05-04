<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Console\Input\ArgvInput;
use Yiisoft\Config\Command\ConfigCommandProvider;

/**
 * ComposerEventHandler responds to composer event. In the package, its job is to copy configs from packages to
 * the application and to prepare a merge plan that is later used by {@see Config}.
 */
final class ComposerEventHandler implements PluginInterface, EventSubscriberInterface, Capable
{
    private ArgvInput $input;

    /**
     * @var string[] Pretty names of updated packages.
     */
    private array $updatedPackagesPrettyNames = [];

    /**
     * @var string[] Names of removed packages.
     */
    private array $removedPackages = [];

    public function __construct()
    {
        $this->input = new ArgvInput();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
            ScriptEvents::POST_INSTALL_CMD => 'onPostUpdateCommandDump',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCommandDump',
        ];
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

    public function onPostAutoloadDump(Event $event): void
    {
        if ($this->runOnAutoloadDump()) {
            $this->processConfigs($event->getComposer(), $event->getIO());
        }
    }

    public function onPostUpdateCommandDump(Event $event): void
    {
        $this->processConfigs($event->getComposer(), $event->getIO());
    }

    public function getCapabilities(): array
    {
        return [CommandProvider::class => ConfigCommandProvider::class];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        // do nothing
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
        $config = new ComposerConfigProcess($composer, $this->updatedPackagesPrettyNames);
        $configFileHandler = new ConfigFileHandler($io, $config->rootPath(), $config->configsDirectory());

        if ($this->runOnCreateProject()) {
            $configFileHandler->handleAfterCreateProject($config->configFiles(), $config->mergePlan());
            return;
        }

        $configFileHandler->handle($config->configFiles(), $this->removedPackages, $config->mergePlan());
    }

    private function runOnAutoloadDump(): bool
    {
        return $this->input->getFirstArgument() === 'dump-autoload';
    }

    private function runOnCreateProject(): bool
    {
        return $this->input->getFirstArgument() === 'create-project';
    }
}
