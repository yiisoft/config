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
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys', 'config'));

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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-root-keys', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-environment-keys', 'config'),
            'environment'
        );

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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-environment-keys', 'config'),
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
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys', 'config'));

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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys-with-params', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [RecursiveMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
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

    public function testNotFoundFile(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/not-found-file', 'config'),
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/broken-file', 'config'),
        );

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('test-error');
        $config->get('params');
    }

    public function testDeepRecursive(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/deep-recursive', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params'),
            ]
        );

        $this->assertSame([
            'nested' => [
                'nested2' => [
                    'nested3-1' => 1,
                    'nested3-2' => 2,
                    'nested3-3' => 3,
                    'nested3-4' => 4,
                ],
            ],
        ], $config->get('params'));
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

    public function testRemoveFromVendorNested(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params'),
                RemoveFromVendor::keys(
                    ['nested', 'nested2']
                ),
            ]
        );

        $this->assertSame([
            'nested' => [
                'nested3' => 3,
                'nested1' => 1,
            ],
            'app' => 42,
        ], $config->get('params'));
    }

    public function testRemoveFromVendorNestedWithReverse(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params'),
                ReverseMerge::groups('params'),
                RemoveFromVendor::keys(
                    ['nested', 'nested2']
                ),
            ]
        );

        $this->assertSame([
            'app' => 42,
            'nested' => [
                'nested1' => 1,
                'nested3' => 3,
            ],
        ], $config->get('params'));
    }

    public function testRemoveFromVendorFromPackage(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor-packages', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params1', 'params2'),
                RemoveFromVendor::keys(
                    ['nested']
                )->package('package/b'),
            ]
        );

        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-app' => 0,
            ],
        ], $config->get('params1'));
        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-app' => 0,
            ],
        ], $config->get('params2'));
    }

    public function testRemoveFromVendorFromPackageGroup(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor-packages', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params1', 'params2'),
                RemoveFromVendor::keys(
                    ['nested']
                )->package('package/b', 'params1'),
            ]
        );

        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-app' => 0,
            ],
        ], $config->get('params1'));
        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-b' => 2,
                'nested-app' => 0,
            ],
        ], $config->get('params2'));
    }

    public function testRemoveFromVendorFromPackageGroup2(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor-packages', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params1', 'params2'),
                RemoveFromVendor::keys(
                    ['nested']
                )->package('package/b', 'params1', 'params2'),
            ]
        );

        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-app' => 0,
            ],
        ], $config->get('params1'));
        $this->assertSame([
            'nested' => [
                'nested-a' => 1,
                'nested-app' => 0,
            ],
        ], $config->get('params2'));
    }

    public function dataRemoveGroupsFromVendor(): array
    {
        return [
            [
                ['*' => '*'],
                [
                    'nested' => [
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-app' => 0,
                    ],
                ]
            ],
            [
                ['*' => 'params1'],
                [
                    'nested' => [
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ]
            ],
            [
                ['package/a' => '*'],
                [
                    'nested' => [
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ]
            ],
            [
                ['package/a' => ['params1', 'params2']],
                [
                    'nested' => [
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ]
            ],
            [
                ['package/a' => 'params1'],
                [
                    'nested' => [
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-b' => 2,
                        'nested-app' => 0,
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataRemoveGroupsFromVendor
     */
    public function testRemoveGroupsFromVendor(array $groups, array $params1, array $params2): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/remove-from-vendor-packages', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params1', 'params2'),
                RemoveFromVendor::groups($groups),
            ]
        );

        $this->assertSame($params1, $config->get('params1'));
        $this->assertSame($params2, $config->get('params2'));
    }

    public function testReverseAndRecursive(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive-reverse', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/variables', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/events', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/events', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursive-reverse', 'config'),
            Options::DEFAULT_ENVIRONMENT,
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
            new ConfigPaths(__DIR__ . '/TestAsset/configs/nested-in-environment', 'config'),
            'environment',
        );

        $this->assertSame([
            'app-base' => 7,
            'only-app' => 42,
            'env-base' => 8,
            'app-backend' => 2,
        ], $config->get('definitions-backend'));
    }

    public function testDoubleDotInPath(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/double-dot-in-path', 'config'),
        );

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $config->get('params'));
    }

    public function dataDefaultEnvironment(): array
    {
        return [
            [null],
            [''],
            ['/'],
        ];
    }

    /**
     * @dataProvider dataDefaultEnvironment
     */
    public function testDefaultEnvironment(?string $environment): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/variables', 'config'),
            $environment,
        );

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $config->get('web'));
    }

    private function createConfig(string $environment = Options::DEFAULT_ENVIRONMENT): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy', 'config'), $environment);
    }
}
