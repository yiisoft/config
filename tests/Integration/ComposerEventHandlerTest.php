<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use PHPUnit\Framework\TestCase;
use function dirname;
use function in_array;
use function file_get_contents;
use Composer\Util\Filesystem;

final class ComposerEventHandlerTest extends TestCase
{
    private array $startComposerConfig = [
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

    private string $stdoutFile;
    private string $stderrFile;
    private string $workingDirectory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->workingDirectory = $this->getWorkingDirectory();

        $tempDirectory = sys_get_temp_dir();
        $this->stdoutFile = $tempDirectory . '/yiisoft-hook-stdout';
        $this->stderrFile = $tempDirectory . '/yiisoft-hook-stderr';
    }

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

    public function testRemovePackageConfig(): void
    {
        $this->execComposer('require first-vendor/first-package');
        $this->assertDirectoryExists($this->workingDirectory . '/config/packages/first-vendor/first-package');

        $this->execComposer('remove first-vendor/first-package');

        // Used this construction without assertDirectoryDoesNotExist
        $this->assertFileDoesNotExist($this->workingDirectory . '/config/packages/first-vendor/first-package');
        $this->assertDirectoryExists($this->workingDirectory . '/config/packages/first-vendor/first-package.removed');
    }

    private function changeInstallationPackagePath(string $path): void
    {
        $composerConfigPath = $this->workingDirectory . '/composer.json';

        $composerArray = $this->getComposerConfigStringAsArray($composerConfigPath);
        $composerArray['repositories'][1]['url'] = '../Packages/' . $path;
        file_put_contents($composerConfigPath, $this->getArrayAsComposerConfigString($composerArray));
    }

    public function testUpdatingPackageWithConfigSimple(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php.dist';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP 2: Updating package (package without changed config)
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEquals($distContentBefore, $distContentAfter);
        $this->assertEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));
    }

    public function testUpdatingToPackageWithChangedConfig(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php.dist';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP 2: Updating package (package with changed config). Shouldn't be warning message
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertNotEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithChangedUserConfig(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php.dist';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP2: Emulating user changes in config file
        file_put_contents($configFilename, PHP_EOL . '//', FILE_APPEND);

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        // STEP 3: Updating package (package with changed config). Should be warning message
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep1(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php.dist';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP2: Emulating user changes in config file
        file_put_contents($configFilename, PHP_EOL . '//', FILE_APPEND);
        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        // STEP 3: Update package (package with changed config). Should be warning message
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertStringContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP 4: Update package (package without changed config). Shouldn't have warning message
        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.4');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep2(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php.dist';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP2: Emulating user changes in config file
        file_put_contents($configFilename, PHP_EOL . '//', FILE_APPEND);
        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        // STEP 3: Update package (package with changed config). Should be with warning message
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));

        // STEP 4: Update package (package with changed config). Should be with warning message
        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString('Config file has been changed. Please re-view file:', file_get_contents($this->stdoutFile));
    }

    private function initComposer(): void
    {
        file_put_contents($this->workingDirectory . '/composer.json', $this->getArrayAsComposerConfigString($this->startComposerConfig));
    }

    private function getArrayAsComposerConfigString(array $array): string
    {
        return \json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function getComposerConfigStringAsArray(string $composerConfigPath): array
    {
        return \json_decode(file_get_contents($composerConfigPath), true);
    }

    private function execComposer(string $command): void
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
        if ((int)$returnCode !== 0) {
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
}
