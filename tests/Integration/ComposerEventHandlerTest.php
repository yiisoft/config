<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use Composer\Util\Filesystem;

use function file_get_contents;

final class ComposerEventHandlerTest extends ComposerTest
{
    private string $reviewConfigPhrase = 'Config file has been changed. Please review';

    protected function getStartComposerConfig(): array
    {
        return [
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
                [
                    'type' => 'path',
                    'url' => '../Packages/second-vendor/second-package',
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
        ];
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

    private function changeInstallationPackagePath(string $path, int $index = 1): void
    {
        $composerConfigPath = $this->workingDirectory . '/composer.json';

        $composerArray = $this->getComposerConfigStringAsArray($composerConfigPath);
        $composerArray['repositories'][$index]['url'] = '../Packages/' . $path;
        file_put_contents($composerConfigPath, $this->getArrayAsComposerConfigString($composerArray));
    }

    public function testUpdatingPackageWithConfigSimple(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

        // STEP 2: Updating package (package without changed config)
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEquals($distContentBefore, $distContentAfter);
        $this->assertEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithConfigAndRemoveDist(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

        // Emulating remove dist file by user
        $fs = new Filesystem();
        $fs->unlink($distConfigFilename);

        // STEP 2: Updating package (package without changed config)
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertFileExists($distConfigFilename);
        $this->assertStringContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
    }

    public function testUpdatingToPackageWithChangedConfig(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package second-vendor/second-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
        $this->assertSameMergePlan([
            'constants' => [
                'first-vendor/first-package' => [
                    'config/constants.php',
                ],
            ],
            'params' => [
                'second-vendor/second-package' => [
                    'config/params.php',
                ],
                'first-vendor/first-package' => [
                    'config/params.php',
                ],
            ],
        ]);

        // Change second package config for test update only updated packages configs
        $secondPackageConfigFileName = $this->workingDirectory . '/config/packages/second-vendor/second-package/config/dist/params.php';
        file_put_contents($secondPackageConfigFileName, '42');

        // STEP 2: Updating package (package with changed config). Shouldn't be warning message
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $contentAfter = file_get_contents($configFilename);
        $distContentAfter = file_get_contents($distConfigFilename);

        $this->assertNotEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

        $this->assertSameMergePlan([
            'constants' => [
                'first-vendor/first-package' => [
                    'config/constants.php',
                ],
            ],
            'params' => [
                'second-vendor/second-package' => [
                    'config/params.php',
                ],
                'first-vendor/first-package' => [
                    'config/params.php',
                ],
            ],
        ]);

        // Assert second package config don't changed
        self::assertSame('42', file_get_contents($secondPackageConfigFileName));
    }

    public function testUpdatingPackageWithChangedUserConfig(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = file_get_contents($configFilename);
        $distContentBefore = file_get_contents($distConfigFilename);

        $this->assertFileExists($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

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
        $this->assertStringContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep1(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

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
        $this->assertStringContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

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
        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep2(): void
    {
        $configFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = $this->workingDirectory . '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeInstallationPackagePath('first-vendor/first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

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
        $this->assertStringContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));

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
        $this->assertStringContainsString($this->reviewConfigPhrase, file_get_contents($this->stdoutFile));
    }
}
