<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class SilentOverrideTest extends ComposerTest
{
    public function testBase(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'first-vendor/first-package' => '*',
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
        ]);

        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $this->assertEnvironmentFileEquals($filePackage, $fileConfig);
    }
}
