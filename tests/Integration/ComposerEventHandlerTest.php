<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;


use PHPUnit\Framework\TestCase;
use function dirname;
use function in_array;

//use function PHPUnit\Framework\directoryExists;

final class ComposerEventHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $workingDirectory = $this->getWorkingDirectory();
        $this->exec("rm -rf $workingDirectory/*");
        file_put_contents($workingDirectory . '/composer.json',
            <<<TXT
{
    "name": "yiisoft/testpackage",
    "type": "library",
    "minimum-stability": "dev",
    "require": {
        "yiisoft/config": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../"
        },
        {
            "type": "path",
            "url": "../Packages/first-vendor/first-package",
            "options": {
              "symlink": false
            }
        }
    ]
}
TXT
        );
        $this->execComposer('install');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $dir = $this->getWorkingDirectory();
        $this->exec("rm -rf $dir/*");
    }

    public function testRemovePackageConfig(): void
    {
        $dir = $this->getWorkingDirectory();

        $this->execComposer('require first-vendor/first-package');
        $this->assertDirectoryExists($dir.'/config/packages/first-vendor/first-package');

        $this->execComposer('remove first-vendor/first-package');

        // Used this construction without assertDirectoryDoesNotExist
        $this->assertFileDoesNotExist($dir . '/config/packages/first-vendor/first-package');
        $this->assertDirectoryExists($dir.'/config/packages/first-vendor/first-package.removed');
    }

    private function execComposer(string $command): void
    {
        $dir = $this->getWorkingDirectory();
        $this->exec("composer $command -d $dir --no-interaction " . $this->suppressLogs());
    }

    private function exec(string $command): void
    {
        $res = exec($command, $_, $returnCode);
        if ((int) $returnCode !== 0) {
            throw new \RuntimeException("$command return code was $returnCode. $res");
        }
    }

    private function getWorkingDirectory(): string
    {
        return dirname(__DIR__) . '/Environment';
    }

    private function suppressLogs(): string
    {
        $commandArguments = $_SERVER['argv'] ?? [];
        $isDebug = in_array('--debug', $commandArguments, true);

        $tempDir = sys_get_temp_dir();

        return !$isDebug ? "2>{$tempDir}/yiisoft-hook" : '';
    }
}
