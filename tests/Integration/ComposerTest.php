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
    protected string $workingDirectory;
    protected string $stdoutFile;
    protected string $stderrFile;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->workingDirectory = dirname(__DIR__) . '/Environment';

        $tempDirectory = sys_get_temp_dir();
        $this->stdoutFile = $tempDirectory . '/yiisoft-hook-stdout';
        $this->stderrFile = $tempDirectory . '/yiisoft-hook-stderr';
    }

    abstract protected function getStartComposerConfig(): array;

    protected function setUp(): void
    {
        parent::setUp();

        $this->removeDirectory($this->workingDirectory);
        $this->ensureDirectoryExists($this->workingDirectory);

        $this->initComposer();
        $this->execComposer('install');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->workingDirectory);
    }

    protected function assertSameMergePlan(array $expected): void
    {
        $mergePlan = require $this->workingDirectory . '/config/packages/merge_plan.php';

        $this->assertSameMergePlanKeys($expected, $mergePlan);

        foreach ($expected as $group => $packages) {
            $this->assertSameMergePlanKeys($packages, $mergePlan[$group]);
            foreach ($packages as $name => $files) {
                self::assertSame($files, $mergePlan[$group][$name]);
            }
        }
    }

    private function assertSameMergePlanKeys(array $expected, array $array): void
    {
        $expectedKeys = array_keys($expected);
        sort($expectedKeys);
        $keys = array_keys($array);
        sort($keys);

        self::assertSame($expectedKeys, $keys);
    }

    private function initComposer(): void
    {
        $config = $this->getStartComposerConfig();

        // Load yiisoft/config dependencies locally
        $packageConfig = $this->getComposerConfigStringAsArray(dirname(__DIR__, 2) . '/composer.lock');
        foreach ($packageConfig['packages'] as $package) {
            $config['repositories'][] = [
                'type' => 'path',
                'url' => '../../vendor/' . $package['name'],
                'options' => [
                    'versions' => [
                        $package['name'] => $package['version'],
                    ],
                ],
            ];
        }

        file_put_contents($this->workingDirectory . '/composer.json', $this->getArrayAsComposerConfigString($config));
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

    protected function changeInstallationPackagePath(string $path, int $index = 1): void
    {
        $composerConfigPath = $this->workingDirectory . '/composer.json';

        $composerArray = $this->getComposerConfigStringAsArray($composerConfigPath);
        $composerArray['repositories'][$index]['url'] = '../Packages/' . $path;
        file_put_contents($composerConfigPath, $this->getArrayAsComposerConfigString($composerArray));
    }

    protected function getArrayAsComposerConfigString(array $array): string
    {
        return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function getComposerConfigStringAsArray(string $composerConfigPath): array
    {
        return json_decode(file_get_contents($composerConfigPath), true);
    }

    protected function removeDirectory(string $directory): void
    {
        (new Filesystem())->removeDirectory($directory);
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        (new Filesystem())->ensureDirectoryExists($directory);
    }
}
