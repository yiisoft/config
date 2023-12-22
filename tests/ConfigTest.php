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
    public function testHas(): void
    {
        $config = $this->createConfig();

        $this->assertTrue($config->has('web'));
        $this->assertTrue($config->has('empty'));
        $this->assertFalse($config->has('not-exist'));
    }

    public function testHasWithEmptyEnvironment(): void
    {
        $config = $this->createConfig('empty');

        $this->assertTrue($config->has('web'));
        $this->assertTrue($config->has('empty'));
        $this->assertFalse($config->has('not-exist'));
    }

    public function testHasWithEnvironment(): void
    {
        $config = $this->createConfig('alfa');

        $this->assertTrue($config->has('web'));
        $this->assertTrue($config->has('empty'));
        $this->assertTrue($config->has('common'));
        $this->assertFalse($config->has('not-exist'));
    }

    public function testGet(): void
    {
        $config = $this->createConfig();

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame([
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ], $config->get('common'));

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-key' => 'b-params-value',
            'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'c-params-key' => 'c-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'c-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'c-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-web-key' => 'root-web-value',
        ], $config->get('web'));
    }

    public function testGetWithEmptyEnvironment(): void
    {
        $config = $this->createConfig('empty');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame([
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ], $config->get('common'));

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-key' => 'b-params-value',
            'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'c-params-key' => 'c-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'c-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'c-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-web-key' => 'root-web-value',
        ], $config->get('web'));
    }

    public function testGetWithEnvironment(): void
    {
        $config = $this->createConfig('alfa');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame($config->get('common'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ]);

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'alfa-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'alfa-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'alfa-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-web-key' => 'root-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
            'alfa-main-key' => 'alfa-main-value',
        ], $config->get('main'));

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-key' => 'b-params-value',
            'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'c-params-key' => 'c-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'alfa-params-key' => 'alfa-params-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'c-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'c-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-web-key' => 'root-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
        ], $config->get('web'));
    }

    public function testGetWithScopeExistenceCheck(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-key' => 'b-params-value',
            'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'c-params-key' => 'c-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'beta-params-key' => 'beta-params-value',
            'beta-params-isset-config' => false,
            'beta-params-isset-params' => false,
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'beta-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'beta-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'beta-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-web-key' => 'root-web-value',
            'beta-web-key' => 'beta-web-value',
            'beta-web-isset-config' => true,
            'beta-web-isset-params' => true,
        ], $config->get('web'));
    }

    public function testGetWithEnvironmentVariableExistAndRootVariableNotExist(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame([
            'root-events-key' => 'root-events-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-key' => 'c-common-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'beta-events-key' => 'beta-events-value',
        ], $config->get('events'));
    }

    public function testGetThrowExceptionForEnvironmentNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration environment does not exist.');
        $this
            ->createConfig('not-exist')
            ->get('web');
    }

    public function testGetThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration group does not exist.');
        $this
            ->createConfig()
            ->get('not-exist');
    }

    public function testGetEnvironmentThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration group does not exist.');
        $this
            ->createConfig('alfa')
            ->get('not-exist');
    }

    public function testGetThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this
            ->createConfig()
            ->get('failVariableGroupEqual');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this
            ->createConfig('alfa')
            ->get('failVariableGroupEqual');
    }

    public function testGetThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "failVariableNotExist" configuration group does not exist.');
        $this
            ->createConfig()
            ->get('failVariableNotExist');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "failVariableNotExist" configuration group does not exist.');
        $this
            ->createConfig('alfa')
            ->get('failVariableNotExist');
    }

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

    public function testConfigWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                ReverseMerge::groups('common', 'params'),
            ]
        );

        $this->assertSame([
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-key-1' => 'root-common-value-1',
            'c-common-key' => 'c-common-value',
            'b-common-key' => 'b-common-value',
            'a-common-key' => 'a-common-value',
        ], $config->get('common'));

        $this->assertSame([
            'root-params-local-key' => 'root-params-local-value',
            'root-params-key' => 'root-params-value',
            'c-params-key' => 'c-params-value',
            'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
            'b-params-key' => 'b-params-value',
            'a-params-key' => 'a-params-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'c-web-override-value',
            'c-web-key' => 'c-web-value',
            'c-web-environment-override-key' => 'c-web-override-value',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'c-common-root-override-key' => 'common-root-override-value',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-key-1' => 'root-common-value-1',
            'c-common-key' => 'c-common-value',
            'b-common-key' => 'b-common-value',
            'a-common-key' => 'a-common-value',
            'root-web-key' => 'root-web-value',
        ], $config->get('web'));
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

    private function createConfig(string $environment = Options::DEFAULT_ENVIRONMENT): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy', 'config'), $environment);
    }
}
