<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\VendorOverrideLayer;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class VendorOverrideLayerTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            ['*/over'],
            ['t*t/over'],
            ['test/ov*'],
            ['test/ov*r'],
            ['test/over'],
            [['test/not-exist', 'test/over', 7]],
        ];
    }

    /**
     * @dataProvider dataBase
     */
    public function testBase(array|string $vendorOverrideLayer): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/over' => __DIR__ . '/packages/over',
            ],
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'web' => 'web.php',
                ],
                'config-plugin-options' => [
                    'vendor-override-layer' => $vendorOverrideLayer,
                ],
            ],
        );

        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'key1' => 'k1-o',
                'key2' => 'app',
                'root-params-key' => 'root-params-value',
            ],
            $config->get('params'),
        );
    }
}
