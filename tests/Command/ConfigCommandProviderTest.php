<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Command;

use Composer\Command\BaseCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Yiisoft\Config\Command\ConfigCommandProvider;
use Yiisoft\Config\Command\CopyCommand;
use Yiisoft\Config\Command\RebuildCommand;

final class ConfigCommandProvideTest extends TestCase
{
    public function testGetCommands(): void
    {
        $provider = new ConfigCommandProvider();

        $this->assertCount(2, $provider->getCommands());
        $this->assertInstanceOf(BaseCommand::class, $provider->getCommands()[0]);
        $this->assertInstanceOf(BaseCommand::class, $provider->getCommands()[1]);
        $this->assertInstanceOf(Command::class, $provider->getCommands()[0]);
        $this->assertInstanceOf(Command::class, $provider->getCommands()[1]);
        $this->assertEquals($provider->getCommands()[0], new CopyCommand());
        $this->assertEquals($provider->getCommands()[1], new RebuildCommand());
    }
}
