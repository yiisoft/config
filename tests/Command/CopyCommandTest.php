<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Command;

use Composer\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Command\CopyCommand;
use Yiisoft\Config\Tests\Composer\TestCase;
use Yiisoft\Config\Tests\TestAsset\TestTrait;

use function file_get_contents;

final class CopyCommandTest extends TestCase
{
    use TestTrait;

    public function testExecuteWithOnePackageFile(): void
    {
        $this->executeCommand('test/custom-source', ['params']);

        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/params.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/web.php'));

        $this->assertFileExists($this->getTempPath('test-custom-source-params.php'));
        $this->assertFileDoesNotExist($this->getTempPath('test-custom-source-web.php'));

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/params.php')),
            file_get_contents($this->getTempPath('test-custom-source-params.php')),
        );
    }

    public function testExecuteWithSeveralPackageFiles(): void
    {
        $this->executeCommand('test/custom-source', ['params.php', 'events', 'common/a']);

        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/common/a.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/common/b.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/params.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/events.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/events-web.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/web.php'));

        $this->assertFileExists($this->getTempPath('test-custom-source-common-a.php'));
        $this->assertFileDoesNotExist($this->getTempPath('test-custom-source-common-b.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-events.php'));
        $this->assertFileDoesNotExist($this->getTempPath('test-custom-source-events-web.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-params.php'));
        $this->assertFileDoesNotExist($this->getTempPath('test-custom-source-web.php'));

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/common/a.php')),
            file_get_contents($this->getTempPath('test-custom-source-common-a.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/events.php')),
            file_get_contents($this->getTempPath('test-custom-source-events.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/params.php')),
            file_get_contents($this->getTempPath('test-custom-source-params.php')),
        );
    }

    public function testExecuteWithAllPackageFiles(): void
    {
        $this->executeCommand('test/custom-source');

        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/common/a.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/common/b.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/events.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/events-web.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/params.php'));
        $this->assertFileExists($this->getSourcePath('custom-source/custom-dir/web.php'));

        $this->assertFileExists($this->getTempPath('test-custom-source-common-a.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-common-b.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-events.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-events-web.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-params.php'));
        $this->assertFileExists($this->getTempPath('test-custom-source-web.php'));

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/common/a.php')),
            file_get_contents($this->getTempPath('test-custom-source-common-a.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/common/b.php')),
            file_get_contents($this->getTempPath('test-custom-source-common-b.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/events.php')),
            file_get_contents($this->getTempPath('test-custom-source-events.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/events-web.php')),
            file_get_contents($this->getTempPath('test-custom-source-events-web.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/params.php')),
            file_get_contents($this->getTempPath('test-custom-source-params.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('custom-source/custom-dir/web.php')),
            file_get_contents($this->getTempPath('test-custom-source-web.php')),
        );
    }

    public function testExecuteWithAllPackageFilesWithDefaultPackageSourceDirectory(): void
    {
        $this->executeCommand('test/a');

        $this->assertFileExists($this->getSourcePath('a/params.php'));
        $this->assertFileExists($this->getSourcePath('a/web.php'));

        $this->assertFileExists($this->getTempPath('test-a-params.php'));
        $this->assertFileExists($this->getTempPath('test-a-web.php'));

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('a/params.php')),
            file_get_contents($this->getTempPath('test-a-params.php')),
        );

        $this->assertEqualStringsIgnoringLineEndings(
            file_get_contents($this->getSourcePath('a/web.php')),
            file_get_contents($this->getTempPath('test-a-web.php')),
        );
    }

    private function executeCommand(string $package, array $files = []): void
    {
        $command = new CopyCommand();
        $command->setComposer($this->createComposerMock());
        $command->setIO($this->createIoMock());
        (new Application())->addCommands([$command]);
        (new CommandTester($command))->execute([
            'package' => $package,
            'target' => '',
            'files' => $files,
        ]);
    }
}
