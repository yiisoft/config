<?php

declare(strict_types=1);

namespace Integration\RemoveFromVendorExtended;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RemoveFromVendorExtendedTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'yiisoft/auth-jwt' => __DIR__ . '/packages/yiisoft-auth-jwt',
            ],
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'params-web' => [
                        '$params',
                        'params-web.php',
                    ],
                ],
                'config-plugin-environments' => [
                    'dev' => [
                        'params' => 'params-dev.php',
                    ],
                ],
            ],
            environment: 'dev',
            paramsGroup: 'params-web',
            modifiers: [
                RecursiveMerge::groups('params', 'params-web'),
                RemoveFromVendor::keys(['yiisoft/auth-jwt', 'algorithms'])->package('yiisoft/auth-jwt', 'params'),
            ],
        );

        $this->assertSame(
            [
                'yiisoft/auth-jwt' => [
                    'algorithms' => [
                        'RS384',
                        'HS512',
                    ],
                ],
                'debug' => true,
                'test' => 7,
            ],
            $config->get('params-web'),
        );
    }
}
