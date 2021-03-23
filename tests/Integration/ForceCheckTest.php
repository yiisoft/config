<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use function dirname;

final class ForceCheckTest extends ComposerTest
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
                    'force-check' => true,
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
        $fileDist = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';
        $filePackage = dirname(__DIR__) . '/Packages/first-vendor/first-package/config/params.php';

        file_put_contents($fileDist, '<?php return [];');
        $this->assertFileNotEquals($filePackage, $fileDist);

        $this->execComposer('du');
        $this->assertFileEquals($filePackage, $fileDist);
    }
}
