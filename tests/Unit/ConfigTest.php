<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testDuplicateKeysErrorMessage(): void
    {
        $configsDir = dirname(__DIR__) . '/configs/duplicate-keys';
        $config = new Config($configsDir, '/config/packages');

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/params.php' . "\n" .
            ' - config/packages/test/a/params.php' . "\n" .
            ' - config/packages/test/b/params.php'
        );
        $config->get('params');
    }

    public function testDuplicateKeysWithPathErrorMessage(): void
    {
        $configsDir = dirname(__DIR__) . '/configs/duplicate-keys-with-params';
        $config = new Config($configsDir, '/packages');

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
        $config->get('params');
    }
}
