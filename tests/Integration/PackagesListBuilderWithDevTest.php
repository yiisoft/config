<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderWithDevTest extends ComposerTest
{
    protected function getStartComposerConfig(): array
    {
        return [
            'name' => 'yiisoft/testpackage',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'require' => [
                'yiisoft/config' => '*',
                'test/d-dev-c' => '*',
            ],
            'require-dev' => [
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
                ],
                [
                    'type' => 'path',
                    'url' => '../Packages/test/c',
                    'options' => [
                        'symlink' => false,
                    ],
                ],
                [
                    'type' => 'path',
                    'url' => '../Packages/test/d-dev-c',
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
        ];
    }

    public function testBase(): void
    {
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

    private function assertMergePlan(array $mergePlan): void
    {
        $this->assertSame($mergePlan, require $this->workingDirectory . '/config/packages/merge_plan.php');
    }
}
