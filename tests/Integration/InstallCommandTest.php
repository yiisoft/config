<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class InstallCommandTest extends ComposerTest
{
    public function testWithoutVendor(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
        ]);

        $this->removeEnvironmentFile('/config/packages/test/a/config/params.php');
        $this->removeEnvironmentFile('/config/packages/test/a/config/web.php');
        $this->removeEnvironmentFile('/config/packages/merge_plan.php');

        $this->assertEnvironmentFileExist('/composer.lock');
        $this->execComposer('install');

        $this->assertEnvironmentFileDoesNotExist('/config/packages/test/a/config/params.php');
        $this->assertEnvironmentFileDoesNotExist('/config/packages/test/a/config/web.php');
        $this->assertEnvironmentFileExist('/config/packages/merge_plan.php');
    }

    public function testWithVendor(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
        ]);

        $this->removeEnvironmentFile('/config/packages/test/a/config/params.php');
        $this->removeEnvironmentFile('/config/packages/test/a/config/web.php');
        $this->removeEnvironmentFile('/config/packages/merge_plan.php');
        $this->removeEnvironmentDirectory('/vendor');

        $this->assertEnvironmentFileExist('/composer.lock');
        $this->execComposer('install');

        $this->assertEnvironmentFileExist('/config/packages/test/a/config/params.php');
        $this->assertEnvironmentFileExist('/config/packages/test/a/config/web.php');
        $this->assertEnvironmentFileExist('/config/packages/merge_plan.php');
    }
}
