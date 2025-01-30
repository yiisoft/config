<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\PackageTypes;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class PackageTypesTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            [
                [
                    'a' => true,
                ],
                null,
            ],
            [
                [
                    'a' => true,
                    'ct' => true,
                ],
                ['custom-type', 'library'],
            ],
            [
                [
                    'ct' => true,
                ],
                ['custom-type'],
            ],
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(array $expected, ?array $packageTypes): void
    {
        $configPluginOptions = $packageTypes === null
            ? []
            : ['package-types' => $packageTypes];

        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/custom-type' => __DIR__ . '/packages/custom-type',
                'test/metapack' => __DIR__ . '/packages/metapack',
            ],
            extra: [
                'config-plugin-options' => $configPluginOptions,
            ],
        );

        $this->assertSame($expected, $config->get('params'));
    }
}
