<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Yiisoft\Config\Command\ConfigCommandProvider;
use Yiisoft\Config\Composer\EventHandler;

final class EventHandlerTest extends \PHPUnit\Framework\TestCase
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
}
