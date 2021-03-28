<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderTest extends ComposerTest
{
    public function testBase(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
            ],
            'extra' => [
                'config-plugin' => [
                    'params' => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'web' => ['config/web.php'],
                ],
            ],
        ]);

        $this->assertMergePlan([
            'params' => [
                '/' => [
                    'config/params.php',
                    '?config/params-local.php',
                ],
            ],
            'web' => [
                '/' => [
                    'config/web.php',
                ],
            ],
        ]);

        $this->execComposer('require test/a');
        $this->assertMergePlan([
            'params' => [
                '/' => [
                    'config/params.php',
                    '?config/params-local.php',
                ],
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                '/' => [
                    'config/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);

        $this->execComposer('require test/ba');
        $this->assertMergePlan([
            'params' => [
                '/' => [
                    'config/params.php',
                    '?config/params-local.php',
                ],
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                '/' => [
                    'config/web.php',
                ],
                'test/ba' => [
                    'config/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);

        $this->execComposer('require test/c');
        $this->assertMergePlan([
            'params' => [
                '/' => [
                    'config/params.php',
                    '?config/params-local.php',
                ],
                'test/c' => [
                    'config/params.php',
                ],
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                '/' => [
                    'config/web.php',
                ],
                'test/ba' => [
                    'config/web.php',
                ],
                'test/c' => [
                    'config/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);

        $this->execComposer('require test/custom-source');
        $this->assertMergePlan([
            'params' => [
                '/' => [
                    'config/params.php',
                    '?config/params-local.php',
                ],
                'test/c' => [
                    'config/params.php',
                ],
                'test/custom-source' => [
                    'params.php',
                ],
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'subdir' => [
                'test/custom-source' => [
                    'subdir/*.php'
                ],
            ],
            'web' => [
                '/' => [
                    'config/web.php',
                ],
                'test/ba' => [
                    'config/web.php',
                ],
                'test/c' => [
                    'config/web.php',
                ],
                'test/custom-source' => [
                    'web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);
    }

    public function testRequireDev(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/d-dev-c' => '*',
            ],
            'require-dev' => [
                'test/a' => '*',
            ],
        ]);

        $this->assertMergePlan([
            'params' => [
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                'test/d-dev-c' => [
                    'config/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);
    }

    public function testSourceDirectoryOption(): void
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

    public function testVariables(): void
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
