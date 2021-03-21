<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderTest extends ComposerTest
{
    protected function getStartComposerConfig(): array
    {
        $packages = [
            'a',
            'ba',
            'c',
        ];

        $repositories = [
            [
                'type' => 'path',
                'url' => '../../',
            ],
        ];
        foreach ($packages as $package) {
            $repositories[] = [
                'type' => 'path',
                'url' => '../Packages/test/' . $package,
                'options' => [
                    'symlink' => false,
                ],
            ];
        }

        return [
            'name' => 'yiisoft/testpackage',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'require' => [
                'yiisoft/config' => '*',
            ],
            'repositories' => $repositories,
            'extra' => [
                'config-plugin' => [
                    'params' => [
                        'config/params.php',
                        '?config/params-local.php',
                    ],
                    'web' => ['config/web.php'],
                ],
            ],
        ];
    }

    public function testBase(): void
    {
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
    }

    private function assertMergePlan(array $mergePlan): void
    {
        $this->assertSame($mergePlan, require $this->workingDirectory . '/config/packages/merge_plan.php');
    }
}
