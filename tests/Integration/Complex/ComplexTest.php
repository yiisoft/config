<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\Complex;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class ComplexTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/ba' => __DIR__ . '/packages/ba',
                'test/c' => __DIR__ . '/packages/c',
                'test/custom-source' => __DIR__ . '/packages/custom-source',
                'test/d-dev-c' => __DIR__ . '/packages/d-dev-c',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                    'vendor-override-layer' => 'test/over',
                ],
                'config-plugin' => [
                    'empty' => [],
                    'common' => 'common/*.php',
                    'params' => [
                        'params.php',
                        '?params-local.php',
                    ],
                    'web' => [
                        '$common',
                        'web.php',
                    ],
                ],
            ],
            configDirectory: 'config',
        );

        $this->assertSame(
            [
                'c-param' => true,
                'custom-source-param' => true,
                'a-param' => true,
                'app-param1' => 1,
                'app-param2' => 2,
            ],
            $config->get('params')
        );
        $this->assertSame(
            [
                'ba-web' => true,
                'c-web' => true,
                'custom-source-web' => true,
                'd-dev-c-web' => true,
                'a-web' => true,
                'custom-source-common-a' => true,
                'custom-source-common-b' => true,
                'common' => 99,
                'web' => 7,
            ],
            $config->get('web')
        );
    }
}
