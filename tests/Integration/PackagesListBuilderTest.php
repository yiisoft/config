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
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/a');
        $this->assertMergePlan([
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
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
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'config/web.php',
                    ],
                    'test/ba' => [
                        'config/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                ],
            ],
        ]);

        $this->execComposer('require test/c');
        $this->assertMergePlan([
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
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
                    Options::DEFAULT_ENVIRONMENT => [
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
            ],
        ]);

        $this->execComposer('require test/custom-source');
        $this->assertMergePlan([
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
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
                    Options::DEFAULT_ENVIRONMENT => [
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
            Options::DEFAULT_ENVIRONMENT => [
                'web' => [
                    'test/d-dev-c' => [
                        'config/web.php',
                    ],
                    'test/a' => [
                        'config/web.php',
                    ],
                ],
                'params' => [
                    'test/a' => [
                        'config/params.php',
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
            Options::DEFAULT_ENVIRONMENT => [
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
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
            Options::DEFAULT_ENVIRONMENT => [
                'common' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'app-configs/common.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
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

    public function testEnvironments(): void
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
                'config-plugin-environments' => [
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
            Options::DEFAULT_ENVIRONMENT => [
                'common' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'app-configs/common.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'app-configs/params.php',
                        '?app-configs/params-local.php',
                    ],
                    'test/a' => [
                        'config/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
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
                    Options::DEFAULT_ENVIRONMENT => [
                        '$web',
                        'alfa/main.php',
                    ],
                ],
                'params' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'alfa/params.php',
                    ],
                ],
                'web' => [
                    Options::DEFAULT_ENVIRONMENT => [
                        'alfa/web.php',
                    ],
                ],
            ],
        ]);
    }
}
