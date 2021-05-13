<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testGet(): void
    {
        $configsDir = dirname(__DIR__) . '/configs/dummy';
        $config = new Config($configsDir, 'config/packages');

        $this->assertSame($config->get('common'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
        ]);

        $this->assertSame($config->get('params'), [
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
        ]);

        $this->assertSame($config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);
    }

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
