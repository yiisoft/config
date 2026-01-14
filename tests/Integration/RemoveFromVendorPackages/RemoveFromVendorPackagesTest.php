<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\RemoveFromVendorPackages;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RemoveFromVendorPackagesTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            'keys-package' => [
                RemoveFromVendor::keys(['nested'])->package('test/b'),
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-app' => 0,
                    ],
                ],
            ],
            'keys-package-and-group' => [
                RemoveFromVendor::keys(['nested'])->package('test/b', 'params1'),
                [
                    'nested' => [
                        'nested-a' => 1,
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
            'keys-package-and-two-groups' => [
                RemoveFromVendor::keys(['nested'])->package('test/b', 'params1', 'params2'),
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-app' => 0,
                    ],
                ],
                [
                    'nested' => [
                        'nested-a' => 1,
                        'nested-app' => 0,
                    ],
                ],
            ],
            'groups-all' => [
                RemoveFromVendor::groups(['*' => '*']),
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
            'groups-group-in-all-packages' => [
                RemoveFromVendor::groups(['*' => 'params1']),
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
            'groups-all-in-package' => [
                RemoveFromVendor::groups(['test/a' => '*']),
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
            'groups-two' => [
                RemoveFromVendor::groups(['test/a' => ['params1', 'params2']]),
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
            'groups-one' => [
                RemoveFromVendor::groups(['test/a' => 'params1']),
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

    #[DataProvider('dataBase')]
    public function testBase(object $modifier, array $params1, array $params2): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/b' => __DIR__ . '/packages/b',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                    'params1' => 'params1.php',
                    'params2' => 'params2.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups('params1', 'params2'),
                $modifier,
            ],
        );

        $this->assertSame($params1, $config->get('params1'));
        $this->assertSame($params2, $config->get('params2'));
    }
}
