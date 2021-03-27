<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderWithSourceDirectoryTest extends ComposerTest
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
                    'web' => 'web.php',
                ],
            ],
        ]);

        $this->assertMergePlan([
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
                    'app-configs/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);
    }
}
