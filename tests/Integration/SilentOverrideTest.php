<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class SilentOverrideTest extends ComposerTest
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
                    'silent-override' => true,
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
        $fileConfig = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $fileDist = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $this->assertFileEquals($fileConfig, $fileDist);
    }
}
