<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\RemoveFromVendorNestedKey;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RemoveFromVendorNestedKeyTest extends IntegrationTestCase
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
                ],
            ],
            modifiers: [
                RecursiveMerge::groups('params'),
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
            ],
            $config->get('params'),
        );
    }
}
