<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\Util\Filesystem;
use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\ConfigFileDiffer;
use Yiisoft\Config\ConfigFileUpdater;
use Yiisoft\Config\Options;

use function dirname;
use function file_get_contents;
use function json_encode;
use function md5;

final class ConfigFileUpdaterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->ensureDirectoryExists(
            "{$this->getWorkingDirectory()}/" . Options::DEFAULT_CONFIGS_DIRECTORY,
        );
    }

    public function forceCheckDataProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
        ];
    }

    public function testUpdateLockFileWithFileNotExists(): void
    {
        $this->createConfigFileUpdater()->updateLockFile();

        $this->assertDistLock($this->getDistLockContent());
    }

    public function testUpdateLockFileWithFileExists(): void
    {
        $this->putPackagesFileContents([Options::DIST_LOCK_FILENAME => '{}']);
        $this->createConfigFileUpdater()->updateLockFile();

        $this->assertDistLock($this->getDistLockContent());
    }

    public function testUpdateLockFileWithRemovedPackages(): void
    {
        $this->putPackagesFileContents([Options::DIST_LOCK_FILENAME => json_encode($this->getDistLockContent())]);
        $this->createConfigFileUpdater()->updateLockFile(['test/custom-source']);

        $this->assertDistLock([]);
    }

    public function testUpdateLockFileWithFileExistsAndNotNeedUpdate(): void
    {
        $this->putPackagesFileContents([
            Options::DIST_LOCK_FILENAME => json_encode(
                $this->getDistLockContent(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ),
        ]);
        $this->createConfigFileUpdater()->updateLockFile();

        $this->assertDistLock($this->getDistLockContent());
    }

    public function testUpdateLockFileWithFileExistsAndWithoutVersionAndChangeHash(): void
    {
        $this->putPackagesFileContents([
            Options::DIST_LOCK_FILENAME => json_encode([
                'test/custom-source' => [
                    'subdir/a.php' => $this->getFileContent('custom-source/custom-dir/subdir/a.php'),
                    'params.php' => $this->getFileContent('custom-source/custom-dir/params.php'),
                    'web.php' => 'changed-hash',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
        $this->createConfigFileUpdater()->updateLockFile();

        $this->assertDistLock([
            'test/custom-source' => [
                'subdir/a.php' => $this->getFileContent('custom-source/custom-dir/subdir/a.php'),
                'params.php' => $this->getFileContent('custom-source/custom-dir/params.php'),
                'web.php' => $this->getFileContent('custom-source/custom-dir/web.php'),
                'version' => '1.0.0',
            ],
        ]);
    }

    public function testUpdateMergePlanWithFileNotExists(): void
    {
        $process = $this->createComposerConfigProcess();
        $this->createConfigFileUpdater($process)->updateMergePlan();

        $this->assertMergePlan($process->mergePlan());
    }

    public function testUpdateMergePlanWithFileExists(): void
    {
        $this->putPackagesFileContents([Options::MERGE_PLAN_FILENAME => '']);
        $process = $this->createComposerConfigProcess();
        $this->createConfigFileUpdater($process)->updateMergePlan();

        $this->assertMergePlan($process->mergePlan());
    }

    private function createConfigFileUpdater(ComposerConfigProcess $process = null): ConfigFileUpdater
    {
        $process ??= new ComposerConfigProcess($this->createComposerMock(), [], true);
        $sourcePath = "{$process->rootPath()}/{$process->configsDirectory()}";

        return new ConfigFileUpdater($process, new ConfigFileDiffer($this->createIoMock(), $sourcePath));
    }

    private function createComposerConfigProcess(): ComposerConfigProcess
    {
        return new ComposerConfigProcess(
            $this->createComposerMock(
                json_decode(file_get_contents(dirname(__DIR__) . '/Packages/custom-source/composer.json'), true)
            ),
            [],
            true,
        );
    }

    private function getDistLockContent(): array
    {
        return [
            'test/custom-source' => [
                'version' => '1.0.0',
                'subdir/a.php' => $this->getFileContent('custom-source/custom-dir/subdir/a.php'),
                'params.php' => $this->getFileContent('custom-source/custom-dir/params.php'),
                'web.php' => $this->getFileContent('custom-source/custom-dir/web.php'),
            ],
        ];
    }

    private function getFileContent(string $file): string
    {
        return md5(json_encode(file_get_contents(dirname(__DIR__) . "/Packages/$file")));
    }
}
