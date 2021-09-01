<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\Package\Package;
use Yiisoft\Config\ConfigFile;
use Yiisoft\Config\ConfigFileDiffer;

use function dirname;

final class ConfigFileDifferTest extends TestCase
{
    public function testDiffWithAddedToPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('params.php', 'added.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/added.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/params.php\n"
            . "= Lines: -3,5 =\n"
            . "-\n"
            . "-// Added comment\n"
        );
    }

    public function testDiffWithChangedPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('params.php', 'changed.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/changed.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/params.php\n"
            . "= Lines: -5,6 +5,6 =\n"
            . "-    'age' => 19,\n"
            . "+    'age' => 42,\n"
        );
    }

    public function testDiffWithDeletedFromPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('params.php', 'deleted.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/deleted.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/params.php\n"
            . "= Lines: +4,8 =\n"
            . "+return [\n"
            . "+    'age' => 42,\n"
            . "+];\n"
            . "+\n"
        );
    }

    public function testDiffWithFilesEqual(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('params.php', 'params.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/params.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/params.php\n"
            . "No differences.\n"
        );
    }

    public function testDiffWithPackageFileNotExists(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('not-exist.php', 'params.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/params.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/not-exist.php\n"
            . "The file \"{$this->getSourcePath()}/diff-files/not-exist.php\" does not exist or is not a file.\n"
        );
    }

    public function testDiffWithVendorFileNotExists(): void
    {
        $this->createConfigFileDiffer()->diff($this->createConfigFile('params.php', 'not-exist.php'));

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/diff-files/not-exist.php\n"
            . "+++ {$this->getSourcePath()}/diff-files/params.php\n"
            . "The file \"{$this->getSourcePath()}/diff-files/not-exist.php\" does not exist or is not a file.\n"
        );
    }

    private function getSourcePath(): string
    {
        return dirname(__DIR__) . '/configs';
    }

    private function createConfigFile(string $sourceFile, string $destinationFile): ConfigFile
    {
        return new ConfigFile(
            new Package('diff-files', '1.0.0', '1.0.0'),
            $destinationFile,
            "{$this->getSourcePath()}/diff-files/$sourceFile",
        );
    }

    private function createConfigFileDiffer(): ConfigFileDiffer
    {
        return new ConfigFileDiffer($this->createIoMock(), $this->getSourcePath());
    }
}
