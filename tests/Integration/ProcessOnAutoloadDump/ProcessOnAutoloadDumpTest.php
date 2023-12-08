<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\ProcessOnAutoloadDump;

use Yiisoft\Config\Options;
use Yiisoft\Config\Tests\Integration\BaseTestCase;

final class ProcessOnAutoloadDumpTest extends BaseTestCase
{
    public function testBase(): void
    {
        $rootPath = __DIR__;
        $packages = [
            'test/a' => __DIR__ . '/packages/a',
            'test/b' => __DIR__ . '/packages/b',
        ];
        $mergePlanPath = $rootPath . '/' . Options::DEFAULT_MERGE_PLAN_FILE;

        $this->runComposerUpdate(
            rootPath: $rootPath,
            packages: $packages,
        );

        $mergePlanContent = file_get_contents($mergePlanPath);
        unlink($mergePlanPath);

        $this->runComposerCommand(
            ['command' => 'dump-autoload'],
            rootPath: $rootPath,
            packages: $packages,
        );

        $this->assertFileExists($mergePlanPath);
        $this->assertSame($mergePlanContent, file_get_contents($mergePlanPath));
    }
}
