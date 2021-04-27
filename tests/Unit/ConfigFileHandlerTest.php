<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\IO\IOInterface;
use Yiisoft\Config\ConfigFileHandler;

final class ConfigFileHandlerTest extends TestCase
{
    public function testHandleAfterCreateProject(): void
    {
        $file1 = 'first/package/file-1.php';
        $file2 = 'first/package/file-2.php';
        $file3 = 'second/package/file-3.php';

        $this->putVendorFileContents([
            $file1 => 'content-1',
            $file2 => 'content-2',
            $file3 => 'content-3',
        ]);

        $this->assertFileExists($this->getVendorPath($file1));
        $this->assertFileExists($this->getVendorPath($file2));
        $this->assertFileExists($this->getVendorPath($file3));

        $this->putPackagesFileContents([
            $file2 => 'changed-2',
            $file3 => 'changed-3',
        ]);

        $this->assertFileDoesNotExist($this->getPackagesPath($file1));
        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertFileExists($this->getPackagesPath($file3));

        $this->assertNotEqualsFileContents($file2);
        $this->assertNotEqualsFileContents($file3);

        $this->createConfigFileHandler($this->createIoMock())->handleAfterCreateProject(
            [
                $this->createConfigFile($file1),
                $this->createConfigFile($file2),
                $this->createConfigFile($file3),
            ],
            $mergePlan = ['package' => ['options']],
        );

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertEqualsFileContents($file1);

        $this->assertNotEqualsFileContents($file2);
        $this->assertNotEqualsFileContents($file3);

        $this->assertMergePlan($mergePlan);

        $this->assertOutputMessages(
            "Config files were changed to run the application template:\n"
            . " - config/packages/first/package/file-2.php\n"
            . " - config/packages/second/package/file-3.php\n"
            . 'You can change any configuration files located in the "config/packages" for yourself.'
        );
    }

    public function testHandleWithSilentOverrideAndWithoutInteractiveMode(): void
    {
        $file1 = 'first/package/file-1.php';
        $file2 = 'first/package/file-2.php';
        $file3 = 'second/package/file-3.php';
        $file4 = 'second/package/file-4.php';
        $packageRemove1 = 'remove/package-1';
        $packageRemove2 = 'remove/package-2';

        $this->putVendorFileContents([
            $file1 => 'content-1',
            $file2 => 'content-2',
            $file3 => 'content-3',
            $file4 => 'content-4',
        ]);

        $this->ensureVendorDirectoryExists($packageRemove1);
        $this->ensureVendorDirectoryExists($packageRemove2);

        $this->assertFileExists($this->getVendorPath($file1));
        $this->assertFileExists($this->getVendorPath($file2));
        $this->assertFileExists($this->getVendorPath($file3));
        $this->assertFileExists($this->getVendorPath($file4));
        $this->assertDirectoryExists($this->getVendorPath($packageRemove1));
        $this->assertDirectoryExists($this->getVendorPath($packageRemove2));


        $this->putPackagesFileContents([
            $file2 => 'changed-2',
            $file3 => 'content-3',
            $file4 => 'changed-3',
            'merge_plan.php' => '',
        ]);

        $this->ensurePackagesDirectoryExists($packageRemove1);

        $this->assertFileDoesNotExist($this->getPackagesPath($file1));

        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertNotEqualsFileContents($file2);

        $this->assertFileExists($this->getPackagesPath($file3));
        $this->assertEqualsFileContents($file3);

        $this->assertFileExists($this->getPackagesPath($file4));
        $this->assertDirectoryExists($this->getPackagesPath($packageRemove1));
        $this->assertDirectoryDoesNotExist($this->getPackagesPath($packageRemove2));
        $this->assertFileExists($this->getPackagesPath('merge_plan.php'));

        $io = $this->createIoMock();
        $io->expects($this->exactly(2))->method('isInteractive')->willReturn(false);
        $io->expects($this->exactly(0))->method('select');
        $io->expects($this->exactly(0))->method('askConfirmation');

        $this->createConfigFileHandler($io)->handle(
            [
                $this->createConfigFile($file1),
                $this->createConfigFile($file2),
                $this->createConfigFile($file3),
                $this->createConfigFile($file4, true),
            ],
            [
                $packageRemove1,
                $packageRemove2,
            ],
            [],
        );

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertEqualsFileContents($file1);
        $this->assertNotEqualsFileContents($file2);
        $this->assertDirectoryExists($this->getPackagesPath($packageRemove1));
        $this->assertMergePlan([]);

        $this->assertOutputMessages(
            "Config files has been added:\n"
            . " - config/packages/first/package/file-1.php\n"
            . "\nConfig files has been updated:\n"
            . " - config/packages/second/package/file-4.php\n"
            . "\nChanges in the config files were ignored:\n"
            . " - config/packages/first/package/file-2.php\n"
            . "Please review the files above and change them yourself if necessary.\n"
            . "\nThe packages were removed from the vendor, but the configurations remained:\n"
            . " - config/packages/remove/package-1\n"
            . 'Please review the files above and remove them yourself if necessary.'
        );
    }

    public function askConfirmationDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider askConfirmationDataProvider
     *
     * @param bool $confirm
     */
    public function testHandleWithAddFileAndIgnoreChoice(bool $confirm): void
    {
        $file1 = 'first/package/file-1.php';
        $file2 = 'first/package/file-2.php';

        $this->putVendorFileContents([
            $file1 => 'content-1',
            $file2 => 'content-2',
        ]);

        $this->assertFileExists($this->getVendorPath($file1));
        $this->assertFileExists($this->getVendorPath($file2));

        $this->putPackagesFileContents([
            $file2 => 'changed-2',
        ]);

        $this->assertFileDoesNotExist($this->getPackagesPath($file1));
        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertNotEqualsFileContents($file2);

        $io = $this->createIoMock();
        $io->expects($this->once())->method('isInteractive')->willReturn(true);
        $io->expects($this->once())->method('select')->willReturn(1);
        $io->expects($this->once())->method('askConfirmation')->willReturn($confirm);

        $handler = $this->createConfigFileHandler($io);
        $handler->handle([$this->createConfigFile($file1), $this->createConfigFile($file2)], [], []);

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertEqualsFileContents($file1);
        $this->assertNotEqualsFileContents($file2);

        $this->assertOutputMessages(
            "Config files has been added:\n"
            . " - config/packages/first/package/file-1.php\n"
            . "\nChanges in the config files were ignored:\n"
            . " - config/packages/first/package/file-2.php\n"
            . 'Please review the files above and change them yourself if necessary.'
        );
    }

    /**
     * @dataProvider askConfirmationDataProvider
     *
     * @param bool $confirm
     */
    public function testHandleWithUpdateChoice(bool $confirm): void
    {
        $file1 = 'first/package/file-1.php';
        $file2 = 'first/package/file-2.php';
        $file3 = 'first/package/file-3.php';

        $this->putVendorFileContents([
            $file1 => 'content-1',
            $file2 => 'content-2',
            $file3 => 'content-3',
        ]);

        $this->assertFileExists($this->getVendorPath($file1));
        $this->assertFileExists($this->getVendorPath($file2));
        $this->assertFileExists($this->getVendorPath($file3));

        $this->putPackagesFileContents([
            $file1 => 'changed-1',
            $file2 => 'changed-2',
            $file3 => 'changed-3',
        ]);

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertNotEqualsFileContents($file1);
        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertNotEqualsFileContents($file2);
        $this->assertFileExists($this->getPackagesPath($file3));
        $this->assertNotEqualsFileContents($file3);

        $io = $this->createIoMock();
        $io->expects($this->exactly(3))->method('isInteractive')->willReturn(true);
        $io->expects($this->exactly($confirm ? 1 : 3))->method('select')->willReturn(2);
        $io->expects($this->once())->method('askConfirmation')->willReturn($confirm);

        $this->createConfigFileHandler($io)->handle(
            [
                $this->createConfigFile($file1),
                $this->createConfigFile($file2),
                $this->createConfigFile($file3),
            ],
            [],
            [],
        );

        $this->assertEqualsFileContents($file1);
        $this->assertEqualsFileContents($file2);
        $this->assertEqualsFileContents($file3);

        $this->assertOutputMessages(
            "Config files has been updated:\n"
            . " - config/packages/first/package/file-1.php\n"
            . " - config/packages/first/package/file-2.php\n"
            . ' - config/packages/first/package/file-3.php'
        );
    }

    /**
     * @dataProvider askConfirmationDataProvider
     *
     * @param bool $confirm
     */
    public function testHandleWithCopyDictChoiceAndRemovePackages(bool $confirm): void
    {
        $file1 = 'first/package/file-1.php';
        $file2 = 'second/package/file-2.php';
        $removePackage1 = 'remove/package-1';
        $removePackage2 = 'remove/package-2';

        $this->putVendorFileContents([
            $file1 => 'content-1',
            $file2 => 'content-2',
        ]);

        $this->ensureVendorDirectoryExists($removePackage1);
        $this->ensureVendorDirectoryExists($removePackage2);

        $this->assertFileExists($this->getVendorPath($file1));
        $this->assertFileExists($this->getVendorPath($file2));
        $this->assertDirectoryExists($this->getVendorPath($removePackage1));
        $this->assertDirectoryExists($this->getVendorPath($removePackage2));

        $this->putPackagesFileContents([
            $file1 => 'changed-1',
            $file2 => 'changed-2',
        ]);

        $this->ensurePackagesDirectoryExists($removePackage1);
        $this->ensurePackagesDirectoryExists($removePackage2);

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertDirectoryExists($this->getPackagesPath($removePackage1));
        $this->assertDirectoryExists($this->getPackagesPath($removePackage2));

        $io = $this->createIoMock();
        $io->expects($this->exactly(4))->method('isInteractive')->willReturn(true);
        $io->expects($this->exactly($confirm ? 1 : 2))->method('select')->willReturn(3);
        $io->expects($this->exactly($confirm ? 3 : 4))->method('askConfirmation')->willReturn($confirm);

        $this->createConfigFileHandler($io)->handle(
            [
                $this->createConfigFile($file1),
                $this->createConfigFile($file2),
            ],
            [
                $removePackage1,
                $removePackage2,
            ],
            [],
        );

        $this->assertFileExists($this->getPackagesPath("$file1.dist"));
        $this->assertFileExists($this->getPackagesPath("$file2.dist"));

        if ($confirm) {
            $this->assertDirectoryDoesNotExist($this->getPackagesPath($removePackage1));
            $this->assertDirectoryDoesNotExist($this->getPackagesPath($removePackage2));
            $this->assertOutputMessages(
                "Config files has been copied with the \".dist\" postfix:\n"
                . " - config/packages/first/package/file-1.php\n"
                . " - config/packages/second/package/file-2.php\n"
                . "Please review files above and change it according with dist files.\n"
                . "\nConfigurations has been removed:\n"
                . " - config/packages/remove/package-1\n"
                . ' - config/packages/remove/package-2'
            );
        } else {
            $this->assertDirectoryExists($this->getPackagesPath($removePackage1));
            $this->assertDirectoryExists($this->getPackagesPath($removePackage2));
            $this->assertOutputMessages(
                "Config files has been copied with the \".dist\" postfix:\n"
                . " - config/packages/first/package/file-1.php\n"
                . " - config/packages/second/package/file-2.php\n"
                . "Please review files above and change it according with dist files.\n"
                . "\nThe packages were removed from the vendor, but the configurations remained:\n"
                . " - config/packages/remove/package-1\n"
                . " - config/packages/remove/package-2\n"
                . 'Please review the files above and remove them yourself if necessary.'
            );
        }
    }

    private function createConfigFileHandler(IOInterface $io): ConfigFileHandler
    {
        return new ConfigFileHandler($io, $this->getWorkingDirectory());
    }
}
