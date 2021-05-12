<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Command\DiffCommand;
use Yiisoft\Config\Tests\Unit\TestCase;

use function dirname;
use function putenv;

final class DiffCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv("COMPOSER={$this->getRootPath()}/composer.json");
    }

    public function testExecuteWithNotControlledPackage(): void
    {
        $this->executeCommand(['test/a']);

        $this->assertOutputMessages("Package(s) \"test/a\" are not controlled by the config plugin.\n");
    }

    public function testExecuteWithoutArgumentsAndFileEquals(): void
    {
        $this->executeCommand([], [
            'config-plugin-options' => [
                'output-directory' => 'tests/configs',
            ],
            'config-plugin' => [
                'web' => 'custom-dir/web.php',
            ],
        ], 'diff-files');

        $this->assertOutputMessages('');
    }

    public function testExecuteWithChangedFile(): void
    {
        $this->executeCommand(['diff-files'], [
            'config-plugin-options' => [
                'output-directory' => 'tests/configs',
            ],
            'config-plugin' => [
                'params' => 'custom-dir/params.php',
            ],
        ], 'diff-files');

        $this->assertOutputMessages(
            "\n= diff-files =\n\n"
            . "--- {$this->getRootPath()}/tests/Packages/custom-source/custom-dir/params.php\n"
            . "+++ {$this->getRootPath()}/tests/configs/diff-files/custom-dir/params.php\n"
            . "= Lines: -4,5 +4,7 =\n"
            . "-return [];\n"
            . "+return [\n"
            . "+    'age' => 42,\n"
            . "+];\n"
            . "= Lines: -6,7 =\n"
            . "-\n"
        );
    }

    public function testExecuteWithFileNotExist(): void
    {
        $this->executeCommand(['test/custom-source'], [
            'config-plugin' => [
                'params' => 'custom-dir/params.php',
            ],
        ]);

        $this->assertOutputMessages(
            "\n= test/custom-source =\n\n"
            . "--- {$this->getRootPath()}/tests/Packages/custom-source/custom-dir/params.php\n"
            . "+++ {$this->getRootPath()}/config/packages/test/custom-source/custom-dir/params.php\n"
            . "The file \"{$this->getRootPath()}/config/packages/test/custom-source/custom-dir/params.php\""
            . " does not exist or is not a file.\n"
        );
    }

    private function getRootPath(): string
    {
        return dirname(__DIR__, 3);
    }

    private function executeCommand(array $packages, array $extra = [], string $customPackageName = null): void
    {
        $command = new DiffCommand();
        $command->setComposer($this->createComposerMock($extra, $customPackageName));
        $command->setIO($this->createIoMock());
        (new CommandTester($command))->execute(['packages' => $packages]);
    }
}
