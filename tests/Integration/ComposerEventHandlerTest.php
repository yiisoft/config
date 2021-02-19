<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;


use PHPUnit\Framework\TestCase;
use function dirname;
use function in_array;
use Composer\Util\Filesystem;

final class ComposerEventHandlerTest extends TestCase
{
    private $startComposerConfig = [
        'name' => 'yiisoft/testpackage',
        'type' => 'library',
        'minimum-stability' => 'dev',
        'require' => [
            'yiisoft/config' => '*',
        ],
        'repositories' => [
            [
                'type' => 'path',
                'url' => '../../',
            ],
            [
                'type' => 'path',
                'url' => '../Packages/first-vendor/first-package',
                'options' => [
                    'symlink' => false,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $workingDirectory = $this->getWorkingDirectory();

        $this->removeDirectory($workingDirectory);
        $this->ensureDirectoryExists($workingDirectory);

        $this->initComposer($workingDirectory);
        $this->execComposer('install');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $workingDirectory = $this->getWorkingDirectory();

        $this->removeDirectory($workingDirectory);
    }

    private function initComposer(string $workingDirectory): void
    {
        file_put_contents($workingDirectory . '/composer.json', $this->getArrayAsComposerConfigString($this->startComposerConfig));
    }

    private function getArrayAsComposerConfigString(array $array): string
    {
        return \json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    private function getComposerConfigStringAsArray(string $composerConfigPath): array
    {
        return \json_decode(file_get_contents($composerConfigPath), true);
    }

    public function testRemovePackageConfig(): void
    {
        $workingDirectory = $this->getWorkingDirectory();

        $this->execComposer('require first-vendor/first-package');
        $this->assertDirectoryExists($workingDirectory.'/config/packages/first-vendor/first-package');

        $this->execComposer('remove first-vendor/first-package');

        // Used this construction without assertDirectoryDoesNotExist
        $this->assertFileDoesNotExist($workingDirectory . '/config/packages/first-vendor/first-package');
        $this->assertDirectoryExists($workingDirectory.'/config/packages/first-vendor/first-package.removed');
    }

    private function execComposer(string $command): void
    {
        $workingDirectory = $this->getWorkingDirectory();
        $this->exec("composer $command -d $workingDirectory --no-interaction " . $this->suppressLogs());
    }

    private function exec(string $command): void
    {
        $result = exec($command, $_, $returnCode);
        if ((int) $returnCode !== 0) {
            throw new \RuntimeException("$command return code was $returnCode. $result");
        }
    }

    private function getWorkingDirectory(): string
    {
        return dirname(__DIR__) . '/Environment';
    }

    private function removeDirectory(string $directory): void
    {
        $fs = new Filesystem();
        $fs->removeDirectory($directory);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        $fs = new Filesystem();
        $fs->ensureDirectoryExists($directory);
    }

    private function suppressLogs(): string
    {
        $commandArguments = $_SERVER['argv'] ?? [];
        $isDebug = in_array('--debug', $commandArguments, true);

        $tempDirectory = sys_get_temp_dir();

        return !$isDebug ? "2>{$tempDirectory}/yiisoft-hook" : '';
    }
}
