<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class OutputDirectoryTest extends ComposerTest
{
    protected function getStartComposerConfig(): array
    {
        return [
            'name' => 'yiisoft/testpackage',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'require' => [
                'yiisoft/config' => '*',
                'first-vendor/first-package' => '*',
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => '../../',
                ],
                [
                    'type' => 'path',
                    'url' => '../Packages/first-vendor/first-package',
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
            'extra' => [
                'config-plugin-options' => [
                    'output-directory' => 'custom-dir/packages',
                ],
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
        $this->assertFileExists($this->workingDirectory . '/custom-dir/packages/merge_plan.php');
    }
}
