<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NestedGroup;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NestedGroupTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin' => [
                    'params' => [],
                    'di' => 'di.php',
                    'di-web' => ['$di', 'di-web.php'],
                ],
            ],
        );

        $this->assertSame(['key' => 42], $config->get('di'));
        $this->assertSame(['key' => 42, 'over' => 1, 'test' => 19], $config->get('di-web'));
    }

    public function testVendorOverrideLayer(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/over' => __DIR__ . '/packages/over',
            ],
            extra: [
                'config-plugin' => [
                    'params' => [],
                    'di' => 'di.php',
                    'di-web' => ['$di', 'di-web.php'],
                ],
                'config-plugin-options' => [
                    'vendor-override-layer' => 'test/over',
                ],
            ],
        );

        $this->assertSame(['key' => 42], $config->get('di'));
        $this->assertSame(['key' => 42, 'over' => 2, 'test' => 19], $config->get('di-web'));
    }
}
