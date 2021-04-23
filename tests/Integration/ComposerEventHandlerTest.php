<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class ComposerEventHandlerTest extends ComposerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
            ],
        ]);
    }

    public function testUpdatingPackageWithConfigSimple(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->assertEnvironmentFileExist($fileConfig);
        $this->assertEquals($packageContentBefore, $configContentBefore);

        // STEP 2: Updating package (package without changed config)
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertEquals($packageContentBefore, $packageContentAfter);
        $this->assertEquals($configContentAfter, $packageContentAfter);
    }

    public function testUpdatingPackageWithRemoveConfigFile(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->execComposer('require first-vendor/first-package');

        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->assertEnvironmentFileExist($fileConfig);
        $this->assertEquals($configContentBefore, $packageContentBefore);

        // Emulating remove file by user
        $this->removeEnvironmentFile($fileConfig);

        // STEP 2: Updating package (package without changed config)
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertEnvironmentFileExist($fileConfig);
    }

    public function testUpdatingToPackageWithChangedConfig(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package second-vendor/second-package');

        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->assertEnvironmentFileExist($fileConfig);
        $this->assertEquals($configContentBefore, $packageContentBefore);
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
        $secondConfigFile = '/config/packages/second-vendor/second-package/config/params.php';
        $this->putEnvironmentFileContents($secondConfigFile, '42');

        // STEP 2: Updating package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertNotEquals($packageContentBefore, $packageContentAfter);
        $this->assertNotEquals($packageContentAfter, $configContentAfter);

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
        self::assertSame('42', $this->getEnvironmentFileContents($secondConfigFile));
    }

    public function testUpdatingPackageWithChangedUserConfig(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->assertEnvironmentFileExist($fileConfig);
        $this->assertEquals($configContentBefore, $packageContentBefore);

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($fileConfig, PHP_EOL . '//', FILE_APPEND);

        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        // STEP 3: Updating package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertNotEquals($packageContentBefore, $packageContentAfter);
        $this->assertNotEquals($configContentAfter, $packageContentAfter);
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep1(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('require first-vendor/first-package');

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($fileConfig, PHP_EOL . '//', FILE_APPEND);
        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        // STEP 3: Update package (package with changed config). Should be warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertNotEquals($packageContentBefore, $packageContentAfter);

        // STEP 4: Update package (package without changed config). Shouldn't have warning message
        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->changeTestPackageDir('first-package', 'first-package-1.0.4');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertEquals($packageContentBefore, $packageContentAfter);
        $this->assertNotEquals($configContentAfter, $packageContentAfter);
    }

    public function testUpdatingPackageWithChangedUserConfigAndNextStep2(): void
    {
        $fileConfig = '/config/packages/first-vendor/first-package/config/params.php';
        $filePackage = '/vendor/first-vendor/first-package/config/params.php';

        // STEP 1: First install
        $this->changeTestPackageDir('first-package', 'first-package-1.0.1');
        $this->execComposer('require first-vendor/first-package');

        // STEP2: Emulating user changes in config file
        $this->putEnvironmentFileContents($fileConfig, PHP_EOL . '//', FILE_APPEND);
        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        // STEP 3: Update package (package with changed config). Should be with warning message
        $this->changeTestPackageDir('first-package', 'first-package-1.0.2-changed-config');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertNotEquals($packageContentBefore, $packageContentAfter);
        $this->assertNotEquals($configContentAfter, $packageContentAfter);

        // STEP 4: Update package (package with changed config). Should be with warning message
        $configContentBefore = $this->getEnvironmentFileContents($fileConfig);
        $packageContentBefore = $this->getEnvironmentFileContents($filePackage);

        $this->changeTestPackageDir('first-package', 'first-package-1.0.3-changed-config');
        $this->execComposer('update');

        $configContentAfter = $this->getEnvironmentFileContents($fileConfig);
        $packageContentAfter = $this->getEnvironmentFileContents($filePackage);

        $this->assertEquals($configContentBefore, $configContentAfter);
        $this->assertNotEquals($packageContentBefore, $packageContentAfter);
        $this->assertNotEquals($configContentAfter, $packageContentAfter);
    }
}
