<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\IO\IOInterface;
use Composer\Package\Package;
use ReflectionClass;
use ReflectionException;
use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\ConfigFile;
use Yiisoft\Config\ConfigFileHandler;
use Yiisoft\Config\Options;

use function array_pop;
use function explode;
use function implode;

final class ConfigFileHandlerTest extends TestCase
{
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

        $io = $this->createIoMock();
        $io->expects($this->exactly(2))->method('isInteractive')->willReturn(false);
        $io->expects($this->exactly(0))->method('select');
        $io->expects($this->exactly(0))->method('askConfirmation');

        $this->createConfigFileHandler($io, [
            $this->createConfigFile($file1),
            $this->createConfigFile($file2),
            $this->createConfigFile($file3),
            $this->createConfigFile($file4, true),
        ])->handle([$packageRemove1, $packageRemove2]);

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertEqualsFileContents($file1);
        $this->assertNotEqualsFileContents($file2);
        $this->assertDirectoryExists($this->getPackagesPath($packageRemove1));

        $this->assertOutputMessages(
            "\nConfig files has been added:\n"
            . " - config/packages/first/package/file-1.php\n"
            . "\nConfig files has been updated:\n"
            . " - config/packages/second/package/file-4.php\n"
            . "\nChanges in the config files were ignored:\n"
            . " - config/packages/first/package/file-2.php\n"
            . "Please review the files above and change them yourself if necessary.\n"
            . "\nThe packages were removed from the vendor, but the configurations remained:\n"
            . " - config/packages/remove/package-1\n"
            . "Please review the files above and remove them yourself if necessary.\n"
        );
    }

    public function testHandleWithShowDiffAndUpdateChoice(): void
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
            $file1 => 'changed-1',
            $file2 => 'changed-2',
        ]);

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertNotEqualsFileContents($file1);
        $this->assertFileExists($this->getPackagesPath($file2));
        $this->assertNotEqualsFileContents($file2);

        $io = $this->createIoMock();
        $io->expects($this->exactly(0))->method('askConfirmation');
        $io->expects($this->exactly(2))->method('isInteractive')->willReturn(true);
        $io->expects($this->exactly(4))->method('select')->willReturnCallback(static function (string $question): int {
            return $question === 'Select one of the following actions:' ? 2 : 4;
        });

        $this->createConfigFileHandler($io, [
            $this->createConfigFile($file1),
            $this->createConfigFile($file2),
        ])->handle();

        $this->assertEqualsFileContents($file1);
        $this->assertEqualsFileContents($file2);

        $this->assertOutputMessages(
            "--- {$this->getVendorPath($file1)}\n"
            . "+++ {$this->getPackagesPath($file1)}\n"
            . "= Lines: -0,1 +0,1 =\n"
            . "-content-1\n"
            . "+changed-1\n"
            . "--- {$this->getVendorPath($file2)}\n"
            . "+++ {$this->getPackagesPath($file2)}\n"
            . "= Lines: -0,1 +0,1 =\n"
            . "-content-2\n"
            . "+changed-2\n"
            . "\nConfig files has been updated:\n"
            . " - config/packages/first/package/file-1.php\n"
            . " - config/packages/first/package/file-2.php\n"
        );
    }

    public function testHandleWithAddFileAndIgnoreChoice(): void
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
        $io->expects($this->exactly(0))->method('askConfirmation');

        $this->createConfigFileHandler($io, [
            $this->createConfigFile($file1),
            $this->createConfigFile($file2),
        ])->handle();

        $this->assertFileExists($this->getPackagesPath($file1));
        $this->assertEqualsFileContents($file1);
        $this->assertNotEqualsFileContents($file2);

        $this->assertOutputMessages(
            "\nConfig files has been added:\n"
            . " - config/packages/first/package/file-1.php\n"
            . "\nChanges in the config files were ignored:\n"
            . " - config/packages/first/package/file-2.php\n"
            . "Please review the files above and change them yourself if necessary.\n"
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

        $this->createConfigFileHandler($io, [
            $this->createConfigFile($file1),
            $this->createConfigFile($file2),
            $this->createConfigFile($file3),
        ])->handle();

        $this->assertEqualsFileContents($file1);
        $this->assertEqualsFileContents($file2);
        $this->assertEqualsFileContents($file3);

        $this->assertOutputMessages(
            "\nConfig files has been updated:\n"
            . " - config/packages/first/package/file-1.php\n"
            . " - config/packages/first/package/file-2.php\n"
            . " - config/packages/first/package/file-3.php\n"
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

        $this->createConfigFileHandler($io, [
            $this->createConfigFile($file1),
            $this->createConfigFile($file2),
        ])->handle([$removePackage1, $removePackage2]);

        $this->assertFileExists($this->getPackagesPath("$file1.dist"));
        $this->assertFileExists($this->getPackagesPath("$file2.dist"));

        if ($confirm) {
            $this->assertDirectoryDoesNotExist($this->getPackagesPath($removePackage1));
            $this->assertDirectoryDoesNotExist($this->getPackagesPath($removePackage2));
            $this->assertOutputMessages(
                "\nConfig files has been copied with the \".dist\" postfix:\n"
                . " - config/packages/first/package/file-1.php\n"
                . " - config/packages/second/package/file-2.php\n"
                . "Please review files above and change it according with dist files.\n"
                . "\nConfigurations has been removed:\n"
                . " - config/packages/remove/package-1\n"
                . " - config/packages/remove/package-2\n"
            );
        } else {
            $this->assertDirectoryExists($this->getPackagesPath($removePackage1));
            $this->assertDirectoryExists($this->getPackagesPath($removePackage2));
            $this->assertOutputMessages(
                "\nConfig files has been copied with the \".dist\" postfix:\n"
                . " - config/packages/first/package/file-1.php\n"
                . " - config/packages/second/package/file-2.php\n"
                . "Please review files above and change it according with dist files.\n"
                . "\nThe packages were removed from the vendor, but the configurations remained:\n"
                . " - config/packages/remove/package-1\n"
                . " - config/packages/remove/package-2\n"
                . "Please review the files above and remove them yourself if necessary.\n"
            );
        }
    }

    protected function assertOutputMessages(string $expected): void
    {
        parent::assertOutputMessages("\n= Yii Config =\n$expected");
    }

    private function createConfigFile(string $file, bool $silentOverride = false): ConfigFile
    {
        $fileParts = explode('/', $file);
        $filename = array_pop($fileParts);

        return new ConfigFile(
            new Package(implode('/', $fileParts), '1.0.0', '1.0.0'),
            $filename,
            $this->getVendorPath($file),
            $silentOverride,
        );
    }

    /**
     * @param IOInterface $io
     * @param ConfigFile[] $files
     * @param array $mergePlan
     *
     * @throws ReflectionException
     *
     * @return ConfigFileHandler
     */
    private function createConfigFileHandler(IOInterface $io, array $files, array $mergePlan = []): ConfigFileHandler
    {
        /** @var ComposerConfigProcess $process */
        $process = (new ReflectionClass(ComposerConfigProcess::class))->newInstanceWithoutConstructor();

        $this->setInaccessibleProperty($process, 'configsDirectory', Options::DEFAULT_CONFIGS_DIRECTORY);
        $this->setInaccessibleProperty($process, 'rootPath', $this->getWorkingDirectory());
        $this->setInaccessibleProperty($process, 'mergePlan', $mergePlan);
        $this->setInaccessibleProperty($process, 'configFiles', $files);
        $this->putPackagesFileContents([Options::DIST_LOCK_FILENAME => '{}']);

        return new ConfigFileHandler($io, $process);
    }

    protected function setInaccessibleProperty(object $object, string $propertyName, $value): void
    {
        $property = (new ReflectionClass($object))->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
    }
}
