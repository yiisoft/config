<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Yiisoft\Config\Command\ConfigCommandProvider;
use Yiisoft\Config\Composer\EventHandler;

final class EventHandlerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                PluginEvents::COMMAND => 'onCommand',
                ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
                ScriptEvents::POST_INSTALL_CMD => 'onPostUpdateCommandDump',
                ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCommandDump',
            ],
            EventHandler::getSubscribedEvents(),
        );
    }

    public function testGetCapabilities(): void
    {
        $this->assertSame(
            [CommandProvider::class => ConfigCommandProvider::class],
            (new EventHandler())->getCapabilities(),
        );
    }

    public function testOnCommand(): void
    {
        $event = $this
            ->getMockBuilder(CommandEvent::class)
            ->onlyMethods(['getCommandName'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $event
            ->method('getCommandName')
            ->willReturn('dump-autoload');

        $handler = new EventHandler();
        $handler->onCommand($event);

        $this->assertTrue($this->getInaccessibleProperty($handler, 'runOnAutoloadDump'));
    }

    public function testOnPostAutoloadDump(): void
    {
        $event = new Event(ScriptEvents::POST_AUTOLOAD_DUMP, $this->createComposerMock(), $this->createIoMock());
        $handler = new EventHandler();
        $this->setInaccessibleProperty($handler, 'runOnAutoloadDump', true);
        $handler->onPostAutoloadDump($event);

        $this->assertMergePlan();
    }

    public function testOnPostUpdateCommandDump(): void
    {
        $event = new Event(ScriptEvents::POST_UPDATE_CMD, $this->createComposerMock(), $this->createIoMock());
        $handler = new EventHandler();
        $handler->onPostUpdateCommandDump($event);

        $this->assertMergePlan();
    }

    public function testUnusedMethods(): void
    {
        $handler = new EventHandler();
        $handler->activate($this->createComposerMock(), $this->createIoMock());
        $handler->activate($this->createComposerMock(), $this->createIoMock());
        $handler->deactivate($this->createComposerMock(), $this->createIoMock());
        $handler->uninstall($this->createComposerMock(), $this->createIoMock());

        $this->assertFalse($this->getInaccessibleProperty($handler, 'runOnAutoloadDump'));
    }
}
