<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class ComposerEventHandlerTest extends ComposerTest
{
    private string $reviewConfigPhrase = 'Config file has been changed. Please review';

    protected function setUp(): void
    {
        parent::setUp();

        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
            ],
        ], ['first-package', 'second-package']);
    }

    public function testRemovePackageConfig(): void
    {
        $this->execComposer('require first-vendor/first-package');
        $this->assertEnvironmentDirectoryExists('/config/packages/first-vendor/first-package');

        $this->execComposer('remove first-vendor/first-package');

        // Used this construction without assertDirectoryDoesNotExist
        $this->assertEnvironmentFileDoesNotExist('/config/packages/first-vendor/first-package');
        $this->assertEnvironmentDirectoryExists('/config/packages/first-vendor/first-package.removed');
    }

    public function testUpdatingPackageWithConfigSimple(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEnvironmentFileExist($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP 2: Updating package (package without changed config)
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEquals($distContentBefore, $distContentAfter);
        $this->assertEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());
    }

    public function testUpdatingPackageWithConfigAndRemoveDist(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEnvironmentFileExist($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());

        // Emulating remove dist file by user
        $this->removeEnvironmentFile($distConfigFilename);

        // STEP 2: Updating package (package without changed config)
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEnvironmentFileExist($distConfigFilename);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());
    }

    public function testUpdatingToPackageWithChangedConfig(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package second-vendor/second-package');

        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEnvironmentFileExist($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());
        $this->assertMergePlan([
            'constants' => [
                'first-vendor/first-package' => [
                    'config/constants.php',
                ],
            ],
            'params' => [
                'first-vendor/first-package' => [
                    'config/params.php',
                ],
                'second-vendor/second-package' => [
                    'config/params.php',
                ],
            ],
        ]);

        // Change second package config for test update only updated packages configs
        $secondPackageConfigFileName = '/config/packages/second-vendor/second-package/config/dist/params.php';
        $this->putEnvironmentFileContents($secondPackageConfigFileName, '42');

        // STEP 2: Updating package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());

        $this->assertMergePlan([
            'constants' => [
                'first-vendor/first-package' => [
                    'config/constants.php',
                ],
            ],
            'params' => [
                'first-vendor/first-package' => [
                    'config/params.php',
                ],
                'second-vendor/second-package' => [
                    'config/params.php',
                ],
            ],
        ]);

        // Assert second package config don't changed
        self::assertSame('42', $this->getEnvironmentFileContents($secondPackageConfigFileName));
    }

    public function testUpdatingPackageWithChangedUserConfig(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEnvironmentFileExist($distConfigFilename);
        $this->assertEquals($contentBefore, $distContentBefore);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($configFilename, PHP_EOL . '//', FILE_APPEND);

        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        // STEP 3: Updating package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep1(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($configFilename, PHP_EOL . '//', FILE_APPEND);
        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        // STEP 3: Update package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP 4: Update package (package without changed config). Shouldn't have warning message
        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->changeTestPackageDir('first-package', 'first-package-1.0.4');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep2(): void
    {
        $configFilename = '/config/packages/first-vendor/first-package/config/params.php';
        $distConfigFilename = '/config/packages/first-vendor/first-package/config/dist/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package');

        $this->assertStringNotContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($configFilename, PHP_EOL . '//', FILE_APPEND);
        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        // STEP 3: Update package (package with changed config). Should be with warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());

        // STEP 4: Update package (package with changed config). Should be with warning message
        $contentBefore = $this->getEnvironmentFileContents($configFilename);
        $distContentBefore = $this->getEnvironmentFileContents($distConfigFilename);

        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $contentAfter = $this->getEnvironmentFileContents($configFilename);
        $distContentAfter = $this->getEnvironmentFileContents($distConfigFilename);

        $this->assertEquals($contentBefore, $contentAfter);
        $this->assertNotEquals($distContentBefore, $distContentAfter);
        $this->assertNotEquals($contentAfter, $distContentAfter);
        $this->assertStringContainsString($this->reviewConfigPhrase, $this->getStdout());
    }
}
