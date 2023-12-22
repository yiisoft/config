<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Options;

final class ConfigTest extends TestCase
{
    public function testDuplicateRootKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys', 'config'));

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
        );

        $config->get('params');
    }

    public function testDuplicateRootKeysErrorMessageWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [ReverseMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
        );
        $config->get('params');
    }

    public function testDuplicateVendorKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys', 'config'));

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - vendor/package/a/params.php' . "\n" .
            ' - vendor/package/b/params.php'
        );

        $config->get('params');
    }

    public function testDuplicateVendorKeysErrorMessageWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [ReverseMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - vendor/package/a/params.php' . "\n" .
            ' - vendor/package/b/params.php'
        );

        $config->get('params');
    }

    public function testConfigWithCustomParams(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/custom-params', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [],
            'custom-params'
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'b-web-override-value',
                'root-web-key' => 42,
            ],
            $config->get('web')
        );
    }

    public function testRemoveFromVendor(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params'),
                RemoveFromVendor::keys(
                    ['b-params-key'],
                    ['array'],
                    ['nested', 'a']
                ),
            ]
        );

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'nested' => [
                'a' => [1],
                'b' => 2,
            ],
            'root-params-key' => 'root-params-value',
            'array' => [7, 8, 9],
        ], $config->get('params'));
    }
}
