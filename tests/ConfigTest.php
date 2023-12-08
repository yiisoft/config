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

    public function testDuplicateEnvironmentKeysErrorMessage(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-environment-keys', 'config'),
            'environment'
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
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
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/environment/params/a.php' . "\n" .
            ' - config/environment/params/b.php'
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

    public function testDuplicateKeysWithRecursiveKeyPathErrorMessage(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-vendor-keys-with-params', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [RecursiveMerge::groups('params')]
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches(
            '~^Duplicate key "name => first-name" in the following configs while building "params" group~',
        );
        $config->get('params');
    }

    public function testCustomMergePlanFile(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/custom-merge-plan-file', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            mergePlanFile: '../merge-plan.php',
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'b-web-override-value',
                'root-web-key' => 'root-params-value',
            ],
            $config->get('web')
        );
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

    public function testConfigWithoutParams(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/without-params', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            paramsGroup: null
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
        $this->expectExceptionMessageMatches(
            '~^The ".*/params2\.php" file does not found\.$~'
        );
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

    public static function dataRecursionDepth(): array
    {
        return [
            'unlimited' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [3, 4, 1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                null,
            ],
            'level0' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                0,
            ],
            'level1' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                1,
            ],
            'level2' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                2,
            ],
            'level3' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [3, 4, 1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                3,
            ],
            'unlimited-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2, 3, 4],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                null,
                true,
            ],
            'level0-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                ],
                0,
                true,
            ],
            'level1-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                1,
                true,
            ],
            'level2-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                2,
                true,
            ],
            'level3-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2, 3, 4],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                3,
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataRecursionDepth
     */
    public function testRecursionDepth(array $expected, ?int $depth, bool $reverse = false): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/recursion-depth', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groupsWithDepth(['params'], $depth),
                ReverseMerge::groups(...($reverse ? ['params'] : [])),
            ]
        );

        $this->assertSame($expected, $config->get('params'));
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
                ],
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
                ],
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
                ],
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
                ],
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
                ],
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

    public function testMergeIndexedArray(): void
    {
        $config = new Config(
            new ConfigPaths(__DIR__ . '/TestAsset/configs/merge-indexed-array', 'config'),
            Options::DEFAULT_ENVIRONMENT,
            [
                RecursiveMerge::groups('params'),
            ]
        );

        $this->assertSame(
            [
                'app' => [
                    'vendor-package-a',
                    'vendor-package-b',
                    'app-1',
                    'app-2',
                ],
            ],
            $config->get('params')
        );
    }

    private function createConfig(string $environment = Options::DEFAULT_ENVIRONMENT): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy', 'config'), $environment);
    }
}
