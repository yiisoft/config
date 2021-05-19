<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\Package;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Repository\CompositeRepository;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Yiisoft\Config\Command\ConfigCommandProvider;
use Yiisoft\Config\ComposerEventHandler;

final class ComposerEventHandlerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                PackageEvents::POST_PACKAGE_INSTALL => 'onPostInstall',
                PackageEvents::POST_PACKAGE_UPDATE => 'onPostUpdate',
                PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostUninstall',
                PluginEvents::COMMAND => 'onCommand',
                ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
                ScriptEvents::POST_INSTALL_CMD => 'onPostUpdateCommandDump',
                ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCommandDump',
            ],
            (new ComposerEventHandler())->getSubscribedEvents(),
        );
    }

    public function testGetCapabilities(): void
    {
        $this->assertSame(
            [CommandProvider::class => ConfigCommandProvider::class],
            (new ComposerEventHandler())->getCapabilities(),
        );
    }

    public function testOnPostInstall(): void
    {
        $operation = $this->getMockBuilder(InstallOperation::class)
            ->onlyMethods(['getPackage'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $operation->method('getPackage')->willReturn(new Package('test/package', '1.0.0', '1.0.0'));
        $event = $this->createPackageEvent(PackageEvents::POST_PACKAGE_INSTALL, $operation);

        $handler = new ComposerEventHandler();
        $handler->onPostInstall($event);

        $this->assertSame(['test/package'], $this->getInaccessibleProperty($handler, 'updatedPackagesPrettyNames'));
    }

    public function testOnPostUpdate(): void
    {
        $operation = $this->getMockBuilder(UpdateOperation::class)
            ->onlyMethods(['getTargetPackage'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $operation->method('getTargetPackage')->willReturn(new Package('test/package', '1.0.0', '1.0.0'));
        $event = $this->createPackageEvent(PackageEvents::POST_PACKAGE_UPDATE, $operation);

        $handler = new ComposerEventHandler();
        $handler->onPostUpdate($event);

        $this->assertSame(['test/package'], $this->getInaccessibleProperty($handler, 'updatedPackagesPrettyNames'));
    }

    public function testOnPostUninstall(): void
    {
        $operation = $this->getMockBuilder(UninstallOperation::class)
            ->onlyMethods(['getPackage'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $operation->method('getPackage')->willReturn(new Package('test/package', '1.0.0', '1.0.0'));
        $event = $this->createPackageEvent(PackageEvents::POST_PACKAGE_UNINSTALL, $operation);

        $handler = new ComposerEventHandler();
        $handler->onPostUninstall($event);

        $this->assertSame(['test/package'], $this->getInaccessibleProperty($handler, 'removedPackages'));
    }

    public function testOnCommand(): void
    {
        $event = $this->getMockBuilder(CommandEvent::class)
            ->onlyMethods(['getCommandName'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $event->method('getCommandName')->willReturn('dump-autoload');

        $handler = new ComposerEventHandler();
        $handler->onCommand($event);

        $this->assertTrue($this->getInaccessibleProperty($handler, 'runOnAutoloadDump'));
    }

    public function testOnPostAutoloadDump(): void
    {
        $event = new Event(ScriptEvents::POST_AUTOLOAD_DUMP, $this->createComposerMock(), $this->createIoMock());
        $handler = new ComposerEventHandler();
        $this->setInaccessibleProperty($handler, 'runOnAutoloadDump', true);
        $handler->onPostAutoloadDump($event);

        $this->assertOutputMessages("\n= Yii Config =\nThe config/packages/dist.lock file was generated.\n");
    }

    public function testOnPostUpdateCommandDump(): void
    {
        $event = new Event(ScriptEvents::POST_UPDATE_CMD, $this->createComposerMock(), $this->createIoMock());
        $handler = new ComposerEventHandler();
        $handler->onPostUpdateCommandDump($event);

        $this->assertOutputMessages("\n= Yii Config =\nThe config/packages/dist.lock file was generated.\n");
    }

    public function testUnusedMethods(): void
    {
        $handler = new ComposerEventHandler();
        $handler->activate($this->createComposerMock(), $this->createIoMock());
        $handler->activate($this->createComposerMock(), $this->createIoMock());
        $handler->deactivate($this->createComposerMock(), $this->createIoMock());
        $handler->uninstall($this->createComposerMock(), $this->createIoMock());

        $this->assertOutputMessages('');
    }

    private function createPackageEvent(string $name, OperationInterface $operation): PackageEvent
    {
        return new PackageEvent(
            $name,
            $this->createComposerMock(),
            $this->createIoMock(),
            true,
            $this->createMock(PolicyInterface::class),
            $this->createMock(Pool::class),
            $this->createMock(CompositeRepository::class),
            $this->createMock(Request::class),
            [],
            $operation,
        );
    }
}
