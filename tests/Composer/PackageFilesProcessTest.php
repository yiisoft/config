<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Yiisoft\Config\Composer\PackageFilesProcess;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Tests\TestAsset\TestTrait;

final class PackageFilesProcessTest extends TestCase
{
    use TestTrait;

    public function testProcessOnePackage(): void
    {
        $process = new PackageFilesProcess($this->createComposerMock(), ['test/ba']);

        $this->assertCount(1, $process->files());

        $this->assertSame('web.php', $process->files()[0]->filename());
        $this->assertSame('config/web.php', $process->files()[0]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('ba/config/web.php'),
            $process->files()[0]->absolutePath(),
        );
    }

    public function testProcessSeveralPackages(): void
    {
        $process = new PackageFilesProcess($this->createComposerMock(), ['test/ba', 'test/d-dev-c']);

        $this->assertCount(2, $process->files());

        $this->assertSame('web.php', $process->files()[0]->filename());
        $this->assertSame('config/web.php', $process->files()[0]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('ba/config/web.php'),
            $process->files()[0]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[1]->filename());
        $this->assertSame('config/web.php', $process->files()[1]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('d-dev-c/config/web.php'),
            $process->files()[1]->absolutePath(),
        );
    }

    public function testProcessAllPackages(): void
    {
        $process = new PackageFilesProcess($this->createComposerMock());

        $this->assertInstanceOf(ConfigPaths::class, $process->paths());
        $this->assertCount(12, $process->files());

        $this->assertSameIgnoringSlash('params.php', $process->files()[0]->filename());
        $this->assertSameIgnoringSlash('config/params.php', $process->files()[0]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('a/config/params.php'),
            $process->files()[0]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[1]->filename());
        $this->assertSame('config/web.php', $process->files()[1]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('a/config/web.php'),
            $process->files()[1]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[2]->filename());
        $this->assertSame('config/web.php', $process->files()[2]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('ba/config/web.php'),
            $process->files()[2]->absolutePath(),
        );

        $this->assertSame('params.php', $process->files()[3]->filename());
        $this->assertSame('config/params.php', $process->files()[3]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('c/config/params.php'),
            $process->files()[3]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[4]->filename());
        $this->assertSame('config/web.php', $process->files()[4]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('c/config/web.php'),
            $process->files()[4]->absolutePath(),
        );

        $this->assertSame('common/a.php', $process->files()[5]->filename());
        $this->assertSame('custom-dir/common/a.php', $process->files()[5]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/common/a.php'),
            $process->files()[5]->absolutePath(),
        );

        $this->assertSame('common/b.php', $process->files()[6]->filename());
        $this->assertSame('custom-dir/common/b.php', $process->files()[6]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/common/b.php'),
            $process->files()[6]->absolutePath(),
        );

        $this->assertSame('events.php', $process->files()[7]->filename());
        $this->assertSame('custom-dir/events.php', $process->files()[7]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/events.php'),
            $process->files()[7]->absolutePath(),
        );

        $this->assertSame('events-web.php', $process->files()[8]->filename());
        $this->assertSame('custom-dir/events-web.php', $process->files()[8]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/events-web.php'),
            $process->files()[8]->absolutePath(),
        );

        $this->assertSame('params.php', $process->files()[9]->filename());
        $this->assertSame('custom-dir/params.php', $process->files()[9]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/params.php'),
            $process->files()[9]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[10]->filename());
        $this->assertSame('custom-dir/web.php', $process->files()[10]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('custom-source/custom-dir/web.php'),
            $process->files()[10]->absolutePath(),
        );

        $this->assertSame('web.php', $process->files()[11]->filename());
        $this->assertSame('config/web.php', $process->files()[11]->relativePath());
        $this->assertSameIgnoringSlash(
            $this->getSourcePath('d-dev-c/config/web.php'),
            $process->files()[11]->absolutePath(),
        );
    }
}
