<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class SourceDirectoryTest extends ComposerTest
{
    public function testBase(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/custom-source' => '*',
            ],
        ]);

        $this->assertEnvironmentFileExist('/config/packages/test/custom-source/params.php');
        $this->assertEnvironmentFileExist('/config/packages/test/custom-source/web.php');
        $this->assertEnvironmentFileExist('/config/packages/test/custom-source/subdir/a.php');
    }
}
