<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use Yiisoft\Config\Options;

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
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/a');
        $this->assertMergePlan([
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'config/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/ba');
        $this->assertMergePlan([
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'config/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                    'test/ba' => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/c');
        $this->assertMergePlan([
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                    'test/c' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'config/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                    'test/ba' => [
                        'config/web.php',
                    ],
                    'test/c' => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/custom-source');
        $this->assertMergePlan([
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                    'test/c' => [
                        'config/params.php',
                    ],
                    'test/custom-source' => [
                        'params.php',
                    ],
                ],
                'subdir' => [
                    'test/custom-source' => [
                        'subdir/*.php'
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'config/web.php',
                    ],
                    'test/a' => [
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
            Options::DEFAULT_BUILD => [
                'params' => [
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    'test/a' => [
                        'config/web.php',
                    ],
                    'test/d-dev-c' => [
                        'config/web.php',
                    ],
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
            Options::DEFAULT_BUILD => [
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
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
            Options::DEFAULT_BUILD => [
                'common' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/common.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        '$common',
                        'app-configs/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);
    }

    public function testAlternativeBuilds(): void
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
                'config-plugin-alternatives' => [
                    'alfa' => [
                        'params' => 'alfa/params.php',
                        'web' => 'alfa/web.php',
                        'main' => [
                            '$web',
                            'alfa/main.php'
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertMergePlan([
            Options::DEFAULT_BUILD => [
                'common' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/common.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        '$common',
                        'app-configs/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                ],
            ],
            'alfa' => [
                'main' => [
                    Options::DEFAULT_BUILD => [
                        '$web',
                        'alfa/main.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_BUILD => [
                        'alfa/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_BUILD => [
                        'alfa/web.php',
                    ],
                ],
            ],
        ]);
    }
}
