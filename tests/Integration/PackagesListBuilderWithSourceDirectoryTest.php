<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderWithSourceDirectoryTest extends ComposerTest
{
    protected function getStartComposerConfig(): array
    {
        return [
            'name' => 'yiisoft/testpackage',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => '../../',
                ],
                [
                    'type' => 'path',
                    'url' => '../Packages/test/a',
                    'options' => [
                        'symlink' => false,
                    ],
                ]
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
        ];
    }

    public function testBase(): void
    {
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

    private function assertMergePlan(array $mergePlan): void
    {
        $this->assertSame($mergePlan, require $this->workingDirectory . '/config/packages/merge_plan.php');
    }
}
