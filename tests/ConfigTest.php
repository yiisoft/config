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

final class ConfigTest extends TestCase
{
    public function testGet(): void
    {
        $config = $this->createConfig();

        $this->assertSame([
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ], $config->get('common'));

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'b-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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

        $this->assertSame($config->get('common'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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
            'b-params-key' => 'b-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'alfa-params-key' => 'alfa-params-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'b-web-override-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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

        $this->assertSame([
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
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
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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

        $this->assertSame([
            'root-events-key' => 'root-events-value',
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
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
        $this->expectErrorMessage('The "not-exist" configuration environment does not exist.');
        $this->createConfig('not-exist')->get('web');
    }

    public function testGetThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->createConfig()->get('not-exist');
    }

    public function testGetEnvironmentThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->createConfig('alfa')->get('not-exist');
    }

    public function testGetThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->createConfig()->get('failVariableGroupEqual');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->createConfig('alfa')->get('failVariableGroupEqual');
    }

    public function testGetThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->createConfig()->get('failVariableNotExist');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->createConfig('alfa')->get('failVariableNotExist');
    }

    public function testDuplicateRootKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys'));

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
        );

        $config->get('params');
    }

    public function testDuplicateRootKeysErrorMessageWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys'),
            null,
            [ReverseMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
        );
        $config->get('params');
    }

    public function testDuplicateEnvironmentKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-environment-keys'), 'environment');

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/environment/params/a.php' . "\n" .
            ' - config/environment/params/b.php'
        );

        $config->get('params');
    }

    public function testDuplicateEnvironmentKeysErrorMessageWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-environment-keys'),
            'environment',
            [ReverseMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/environment/params/a.php' . "\n" .
            ' - config/environment/params/b.php'
        );
        $config->get('params');
    }

    public function testDuplicateVendorKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys'));

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - vendor/package/a/params.php' . "\n" .
            ' - vendor/package/b/params.php'
        );

        $config->get('params');
    }

    public function testDuplicateVendorKeysErrorMessageWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys'),
            null,
            [ReverseMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - vendor/package/a/params.php' . "\n" .
            ' - vendor/package/b/params.php'
        );

        $config->get('params');
    }

    public function testDuplicateKeysWithRecursiveKeyPathErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys-with-params'), null, [
            RecursiveMerge::groups('params'),
        ]);

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
        $config->get('params');
    }

    public function testConfigWithCustomParams(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/custom-params'),
            null,
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

    public function testNotFoundFile(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/not-found-file'),
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches(
            '~^The ".*/params2\.php" file does not found\.$~'
        );
        $config->get('params');
    }

    public function testBrokenFile(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/broken-file'),
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('test-error');
        $config->get('params');
    }

    public function testConfigWithReverseMerge(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy'),
            null,
            [
                ReverseMerge::groups('common', 'params'),
            ]
        );

        $this->assertSame([
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-key-1' => 'root-common-value-1',
            'b-common-key' => 'b-common-value',
            'a-common-key' => 'a-common-value',
        ], $config->get('common'));

        $this->assertSame([
            'root-params-local-key' => 'root-params-local-value',
            'root-params-key' => 'root-params-value',
            'b-params-key' => 'b-params-value',
            'a-params-key' => 'a-params-value',
        ], $config->get('params'));

        $this->assertSame([
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'b-web-override-value',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-key-1' => 'root-common-value-1',
            'b-common-key' => 'b-common-value',
            'a-common-key' => 'a-common-value',
            'root-web-key' => 'root-web-value',
        ], $config->get('web'));
    }

    public function testRemoveFromVendor(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive'),
            null,
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
            'root-params-key' => 'root-params-value',
            'array' => [7, 8, 9],
            'nested' => [
                'a' => [1],
                'b' => 2,
            ],
        ], $config->get('params'));
    }

    public function testReverseAndRecursive(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive-reverse'),
            null,
            [
                RecursiveMerge::groups('params'),
                ReverseMerge::groups('params'),
            ]
        );

        $this->assertSame([
            'array' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
            'nested' => [
                'nested-key' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
            ],
        ], $config->get('params'));
    }

    public function testReverseWithTwoVariables(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/variables'),
            null,
            [
                ReverseMerge::groups('web'),
            ]
        );

        $this->assertSame([
            'b' => 2,
            'a' => 1,
        ], $config->get('web'));
    }

    public function testEvents(): void
    {
        $eventGroups = ['events', 'events-console'];
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/events'),
            null,
            [
                RecursiveMerge::groups(...$eventGroups),
                ReverseMerge::groups(...$eventGroups),
            ]
        );

        $this->assertSame([
            'e1' => [
                ['app3', 'handler1'],
                ['app1', 'handler1'],
                ['package-b1', 'handler1'],
                ['package-a1', 'handler1'],
                ['package-a2', 'handler1'],
                ['package-a3', 'handler1'],
            ],
            'e2' => [
                ['app2', 'handler2'],
                ['package-b2', 'handler1'],
            ],
        ], $config->get('events-console'));
    }

    public function testReverseAndRemoveFromVendor(): void
    {
        $eventGroups = ['events', 'events-console'];
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/events'),
            null,
            [
                RecursiveMerge::groups(...$eventGroups),
                ReverseMerge::groups(...$eventGroups),
                RemoveFromVendor::keys(['e2']),
            ]
        );

        $this->assertSame([
            'e1' => [
                ['app3', 'handler1'],
                ['app1', 'handler1'],
                ['package-b1', 'handler1'],
                ['package-a1', 'handler1'],
                ['package-a2', 'handler1'],
                ['package-a3', 'handler1'],
            ],
            'e2' => [
                ['app2', 'handler2'],
            ],
        ], $config->get('events-console'));
    }

    public function testReverseAndRemoveNestedKeyFromVendor(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive-reverse'),
            null,
            [
                RecursiveMerge::groups('params'),
                ReverseMerge::groups('params'),
                RemoveFromVendor::keys(['nested', 'nested-key']),
            ]
        );

        $this->assertSame([
            'array' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
            'nested' => [
                'nested-key' => [7, 8, 9],
            ],
        ], $config->get('params'));
    }

    public function testNestedGroupInEnvironment(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/nested-in-environment'),
            'environment',
        );

        $this->assertSame([
            'app-base' => 7,
            'only-app' => 42,
            'env-base' => 8,
            'app-backend' => 2,
        ], $config->get('definitions-backend'));
    }

    private function createConfig(string $environment = null): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy'), $environment);
    }
}
