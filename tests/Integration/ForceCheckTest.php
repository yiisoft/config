<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class ForceCheckTest extends ComposerTest
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
        ]);

        $fileDist = '/config/packages/first-vendor/first-package/config/dist/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        $this->putEnvironmentFileContents($fileDist, '<?php return [];');
        $this->assertEnvironmentFileNotEquals($filePackage, $fileDist);

        $this->execComposer('du');
        $this->assertEnvironmentFileEquals($filePackage, $fileDist);
    }
}
