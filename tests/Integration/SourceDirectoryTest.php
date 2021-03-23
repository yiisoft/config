<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class SourceDirectoryTest extends ComposerTest
{
    protected function getStartComposerConfig(): array
    {
        return [
            'name' => 'yiisoft/testpackage',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'require' => [
                'yiisoft/config' => '*',
                'test/custom-source' => '*',
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => '../../',
                ],
                [
                    'type' => 'path',
                    'url' => '../Packages/test/custom-source',
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
        ];
    }

    public function testBase(): void
    {
        $this->assertFileExists($this->workingDirectory . '/config/packages/test/custom-source/params.php');
        $this->assertFileExists($this->workingDirectory . '/config/packages/test/custom-source/web.php');
    }
}
