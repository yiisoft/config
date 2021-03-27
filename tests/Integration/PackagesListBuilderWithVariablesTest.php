<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderWithVariablesTest extends ComposerTest
{
    public function testBase(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
            'extra' => [
                'config-plugin-options' => [
                    'source-directory' => 'app-configs',
                ],
                'config-plugin' => [
                    'params' => [
                        'params.php',
                        '?params-local.php',
                    ],
                    'common' => 'common.php',
                    'web' => [
                        '$common',
                        'web.php',
                    ],
                ],
            ],
        ]);

        $this->assertMergePlan([
            'common' => [
                '/' => [
                    'app-configs/common.php',
                ],
            ],
            'params' => [
                '/' => [
                    'app-configs/params.php',
                    '?app-configs/params-local.php',
                ],
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                '/' => [
                    '$common',
                    'app-configs/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);
    }
}
