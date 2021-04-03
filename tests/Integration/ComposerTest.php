<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function dirname;
use function in_array;

abstract class ComposerTest extends TestCase
{
    private const TEST_PACKAGES = [
        'a',
        'ba',
        'c',
        'custom-source',
        'd-dev-c',
        'first-package',
        'k',
        'second-package',
    ];

    private string $workingDirectory;
    private string $stdoutFile;
    private string $stderrFile;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $tempDirectory = sys_get_temp_dir() . '/yiisoft/config';

        $this->workingDirectory = $tempDirectory . '/Environment';

        $this->ensureDirectoryExists($tempDirectory);
        $this->stdoutFile = $tempDirectory . '/hook-stdout';
        $this->stderrFile = $tempDirectory . '/hook-stderr';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->removeDirectory($this->workingDirectory);
        $this->ensureDirectoryExists($this->workingDirectory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->workingDirectory);
    }

    protected function assertMergePlan(array $expected): void
    {
        $this->assertSame($expected, require $this->workingDirectory . '/config/packages/merge_plan.php');
    }

    protected function assertEnvironmentDirectoryExists(string $directory): void
    {
        $this->assertDirectoryExists($this->workingDirectory . $directory);
    }

    protected function assertEnvironmentFileDoesNotExist(string $filename): void
    {
        $this->assertFileDoesNotExist($this->workingDirectory . $filename);
    }

    protected function assertEnvironmentFileExist(string $filename): void
    {
        $this->assertFileExists($this->workingDirectory . $filename);
    }

    protected function assertEnvironmentFileNotEquals(string $expected, string $actual): void
    {
        $this->assertFileNotEquals($this->workingDirectory . $expected, $this->workingDirectory . $actual);
    }

    protected function assertEnvironmentFileEquals(string $expected, string $actual): void
    {
        $this->assertFileEquals($this->workingDirectory . $expected, $this->workingDirectory . $actual);
    }

    protected function getEnvironmentFileContents(string $filename): string
    {
        return file_get_contents($this->workingDirectory . $filename);
    }

    protected function putEnvironmentFileContents(string $filename, string $content, $context = null): void
    {
        $context === null
            ? file_put_contents($this->workingDirectory . $filename, $content)
            : file_put_contents($this->workingDirectory . $filename, $content, $context);
    }

    protected function removeEnvironmentFile(string $filename): void
    {
        (new Filesystem())->unlink($this->workingDirectory . $filename);
    }

    protected function getStdout(): string
    {
        return file_get_contents($this->stdoutFile);
    }

    protected function initComposer(array $config): void
    {
        $root = $this->getRoot();

        // Load yiisoft/config locally
        $repositories = [
            [
                'type' => 'path',
                'url' => $root,
            ],
        ];

        // Load yiisoft/config dependencies locally
        $packageConfig = $this->getComposerJson(dirname(__DIR__, 2) . '/composer.lock');
        foreach ($packageConfig['packages'] as $package) {
            $repositories[] = [
                'type' => 'path',
                'url' => $root . '/vendor/' . $package['name'],
                'options' => [
                    'versions' => [
                        $package['name'] => $package['version'],
                    ],
                ],
            ];
        }

        // Load test packages
        foreach (self::TEST_PACKAGES as $package) {
            $repositories[] = [
                'type' => 'path',
                'url' => $root . '/tests/Packages/' . $package,
                'options' => [
                    'symlink' => false,
                    '__name' => $package,
                ],
            ];
        }

        $config = array_merge($config, [
            'name' => 'yiisoft/test-package',
            'type' => 'library',
            'minimum-stability' => 'dev',
            'repositories' => $repositories,
        ]);

        $this->setComposerJson($config);
        $this->execComposer('install');
    }

    private function setComposerJson(array $config): void
    {
        file_put_contents(
            $this->workingDirectory . '/composer.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function execComposer(string $command): void
    {
        $commandArguments = $_SERVER['argv'] ?? [];
        $isDebug = in_array('--debug', $commandArguments, true);

        $cliCommand = "composer $command -d {$this->workingDirectory} --no-interaction >{$this->stdoutFile} 2>{$this->stderrFile}";

        $this->exec($cliCommand);
        if ($isDebug) {
            echo 'COMMAND: ' . $cliCommand . PHP_EOL;
            echo 'STDOUT:' . PHP_EOL . file_get_contents($this->stdoutFile);
            echo 'STDERR:' . PHP_EOL . file_get_contents($this->stderrFile) . PHP_EOL;
        }
    }

    private function exec(string $command): void
    {
        $result = exec($command, $_, $returnCode);
        if ($returnCode !== 0) {
            throw new RuntimeException("$command return code was $returnCode. $result");
        }
    }

    protected function changeTestPackageDir(string $package, string $dir): void
    {
        $config = $this->getComposerJson();
        foreach ($config['repositories'] as $i => $data) {
            if (($data['options']['__name'] ?? null) === $package) {
                $config['repositories'][$i]['url'] = $this->getRoot() . '/tests/Packages/' . $dir;
                break;
            }
        }

        $this->setComposerJson($config);
    }

    private function getComposerJson(string $path = null): array
    {
        if ($path === null) {
            $path = $this->workingDirectory . '/composer.json';
        }

        return json_decode(file_get_contents($path), true);
    }

    private function removeDirectory(string $directory): void
    {
        (new Filesystem())->removeDirectory($directory);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        (new Filesystem())->ensureDirectoryExists($directory);
    }

    private function getRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
