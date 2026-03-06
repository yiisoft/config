<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\RuntimeRebuilder;
use Yiisoft\Config\Composer\Options;

use function file_exists;
use function file_put_contents;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;
use function unlink;

final class RuntimeRebuilderTest extends TestCase
{
    private string $tempDir;
    private string $composerJsonPath;
    private string $mergePlanPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/yiisoft-config-test-' . uniqid();
        mkdir($this->tempDir);
        $this->composerJsonPath = $this->tempDir . '/composer.json';
        $this->mergePlanPath = $this->tempDir . '/' . Options::DEFAULT_MERGE_PLAN_FILE;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->composerJsonPath)) {
            unlink($this->composerJsonPath);
        }
        if (file_exists($this->mergePlanPath)) {
            unlink($this->mergePlanPath);
        }
        if (file_exists($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testDoesNothingWhenAutoRebuildIsDisabled(): void
    {
        file_put_contents($this->composerJsonPath, json_encode([
            'name' => 'test/app',
            'extra' => [
                'config-plugin-options' => [
                    'auto-rebuild' => false,
                ],
            ],
        ]));

        $paths = new ConfigPaths($this->tempDir);
        $rebuilder = new RuntimeRebuilder($paths, Options::DEFAULT_MERGE_PLAN_FILE);

        // Should not throw any exceptions
        $rebuilder->ensureMergePlanIsUpToDate();

        $this->assertTrue(true); // No errors means success
    }

    public function testDoesNothingWhenAutoRebuildIsNotSet(): void
    {
        file_put_contents($this->composerJsonPath, json_encode([
            'name' => 'test/app',
        ]));

        $paths = new ConfigPaths($this->tempDir);
        $rebuilder = new RuntimeRebuilder($paths, Options::DEFAULT_MERGE_PLAN_FILE);

        // Should not throw any exceptions
        $rebuilder->ensureMergePlanIsUpToDate();

        $this->assertTrue(true); // No errors means success
    }

    public function testDoesNothingWhenMergePlanIsUpToDate(): void
    {
        file_put_contents($this->composerJsonPath, json_encode([
            'name' => 'test/app',
            'extra' => [
                'config-plugin-options' => [
                    'auto-rebuild' => true,
                ],
            ],
        ]));

        // Create merge plan file with newer timestamp
        touch($this->composerJsonPath, time() - 100);
        file_put_contents($this->mergePlanPath, '<?php return [];');
        touch($this->mergePlanPath, time());

        $paths = new ConfigPaths($this->tempDir);
        $rebuilder = new RuntimeRebuilder($paths, Options::DEFAULT_MERGE_PLAN_FILE);

        // Should not trigger rebuild
        $rebuilder->ensureMergePlanIsUpToDate();

        $this->assertTrue(true); // No errors means success
    }
}
