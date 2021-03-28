<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class OutputDirectoryTest extends ComposerTest
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
        ]);

        $this->assertEnvironmentFileExist('/custom-dir/packages/merge_plan.php');
    }
}
