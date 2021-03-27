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
}
