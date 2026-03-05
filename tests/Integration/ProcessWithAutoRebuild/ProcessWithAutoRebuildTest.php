<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\ProcessWithAutoRebuild;

use Yiisoft\Config\Composer\Options;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class ProcessWithAutoRebuildTest extends IntegrationTestCase
{
    public function testRebuildOnDumpAutoload(): void
    {
        $rootPath = __DIR__;
        $packages = [
            'test/a' => __DIR__ . '/packages/a',
            'test/b' => __DIR__ . '/packages/b',
        ];
        $mergePlanPath = $rootPath . '/' . Options::DEFAULT_MERGE_PLAN_FILE;
        $extra = [
            'config-plugin-options' => [
                'auto-rebuild' => true,
            ],
            'config-plugin' => [
                'params' => [],
            ],
        ];

        $this->runComposerUpdate(
            rootPath: $rootPath,
            packages: $packages,
            extra: $extra,
        );

        $mergePlanContent = file_get_contents($mergePlanPath);
        unlink($mergePlanPath);

        $this->runComposerCommand(
            ['command' => 'dump-autoload'],
            rootPath: $rootPath,
            packages: $packages,
            extra: $extra,
        );

        $this->assertFileExists($mergePlanPath);
        $this->assertSame($mergePlanContent, file_get_contents($mergePlanPath));
    }

    public function testRebuildOnUpdate(): void
    {
        $rootPath = __DIR__;
        $packages = [
            'test/a' => __DIR__ . '/packages/a',
            'test/b' => __DIR__ . '/packages/b',
        ];
        $mergePlanPath = $rootPath . '/' . Options::DEFAULT_MERGE_PLAN_FILE;
        $extra = [
            'config-plugin-options' => [
                'auto-rebuild' => true,
            ],
            'config-plugin' => [
                'params' => [],
            ],
        ];

        $this->runComposerUpdate(
            rootPath: $rootPath,
            packages: $packages,
            extra: $extra,
        );

        $this->assertFileExists($mergePlanPath);
    }
}
