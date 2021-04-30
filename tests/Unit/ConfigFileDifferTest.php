<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Yiisoft\Config\ConfigFile;
use Yiisoft\Config\ConfigFileDiffer;

use function dirname;

final class ConfigFileDifferTest extends TestCase
{
    public function testDiffWithAddedToPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/params.php", 'added.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/added.php\n"
            . "= Lines: +3,5 =\n"
            . "+\n"
            . "+// Added comment\n"
        );
    }

    public function testDiffWithChangedPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/params.php", 'changed.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/changed.php\n"
            . "= Lines: -5,6 +5,6 =\n"
            . "-    'age' => 42,\n"
            . "+    'age' => 19,\n"
        );
    }

    public function testDiffWithDeletedFromPackageFile(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/params.php", 'deleted.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/deleted.php\n"
            . "= Lines: -4,8 =\n"
            . "-return [\n"
            . "-    'age' => 42,\n"
            . "-];\n"
            . "-\n"
        );
    }

    public function testDiffWithFilesEqual(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/params.php", 'params.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/params.php\n"
            . "No differences.\n"
        );
    }

    public function testDiffWithPackageFileNotExists(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/not-exist.php", 'params.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/not-exist.php\n"
            . "+++ {$this->getSourcePath()}/params.php\n"
            . "The file \"{$this->getSourcePath()}/not-exist.php\" does not exist or is not a file.\n"
        );
    }

    public function testDiffWithVendorFileNotExists(): void
    {
        $this->createConfigFileDiffer()->diff(
            new ConfigFile("{$this->getSourcePath()}/params.php", 'not-exist.php'),
        );

        $this->assertOutputMessages(
            "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/not-exist.php\n"
            . "The file \"{$this->getSourcePath()}/not-exist.php\" does not exist or is not a file.\n"
        );
    }

    public function testDiffPackage(): void
    {
        $this->createConfigFileDiffer()->diffPackage('package/name', [
            new ConfigFile("{$this->getSourcePath()}/params.php", 'added.php'),
            new ConfigFile("{$this->getSourcePath()}/params.php", 'changed.php'),
            new ConfigFile("{$this->getSourcePath()}/params.php", 'deleted.php'),
            new ConfigFile("{$this->getSourcePath()}/params.php", 'params.php'),
            new ConfigFile("{$this->getSourcePath()}/params.php", 'not-exist.php')
        ]);

        $this->assertOutputMessages(
            "\n= package/name =\n\n"
            . "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/added.php\n"
            . "= Lines: +3,5 =\n"
            . "+\n"
            . "+// Added comment\n"
            . "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/changed.php\n"
            . "= Lines: -5,6 +5,6 =\n"
            . "-    'age' => 42,\n"
            . "+    'age' => 19,\n"
            . "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/deleted.php\n"
            . "= Lines: -4,8 =\n"
            . "-return [\n"
            . "-    'age' => 42,\n"
            . "-];\n"
            . "-\n"
            . "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/params.php\n"
            . "No differences.\n"
            . "--- {$this->getSourcePath()}/params.php\n"
            . "+++ {$this->getSourcePath()}/not-exist.php\n"
            . "The file \"{$this->getSourcePath()}/not-exist.php\" does not exist or is not a file.\n",
        );
    }

    private function getSourcePath(): string
    {
        return dirname(__DIR__) . '/configs/diff-files';
    }

    private function createConfigFileDiffer(): ConfigFileDiffer
    {
        return new ConfigFileDiffer($this->createIoMock(), $this->getSourcePath());
    }
}
