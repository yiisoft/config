<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function strtr;
use function sys_get_temp_dir;
use function trim;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private const REPLACE_LINE_BREAKS = ["\r\n" => "\n", "\r" => "\n"];
    private const MERGE_PLAN_FILE = '/merge_plan.php';
    private const PACKAGES_DIR = '/config/packages';
    private const VENDOR_DIR = '/vendor';

    private Filesystem $filesystem;
    private string $workingDirectory;
    private string $output = '';

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->filesystem = new Filesystem();
        $this->workingDirectory = sys_get_temp_dir() . '/yiisoft/config/Unit';
    }

    protected function setUp(): void
    {
        $this->filesystem->ensureDirectoryExists($this->workingDirectory);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->filesystem->removeDirectory($this->workingDirectory);
    }

    protected function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    protected function getPackagesPath(string $file): string
    {
        return $this->workingDirectory . self::PACKAGES_DIR . '/' . trim($file, '/');
    }

    protected function getVendorPath(string $file): string
    {
        return $this->workingDirectory . self::VENDOR_DIR . '/' . trim($file, '/');
    }

    protected function ensurePackagesDirectoryExists(string $directory): void
    {
        $this->filesystem->ensureDirectoryExists($this->getPackagesPath($directory));
    }

    protected function ensureVendorDirectoryExists(string $directory): void
    {
        $this->filesystem->ensureDirectoryExists($this->getVendorPath($directory));
    }

    protected function putPackagesFileContents(array $contents): void
    {
        foreach ($contents as $file => $content) {
            $file = $this->getPackagesPath($file);
            $this->filesystem->ensureDirectoryExists(dirname($file));
            file_put_contents($file, $content);
        }
    }

    protected function putVendorFileContents(array $contents): void
    {
        foreach ($contents as $file => $content) {
            $file = $this->getVendorPath($file);
            $this->filesystem->ensureDirectoryExists(dirname($file));
            file_put_contents($file, $content);
        }
    }

    protected function assertEqualsFileContents(string $file): void
    {
        $this->assertTrue($this->equalsFileContents($file));
    }

    protected function assertNotEqualsFileContents(string $file): void
    {
        $this->assertFalse($this->equalsFileContents($file));
    }

    protected function equalsFileContents(string $file): bool
    {
        $contentFromPackages = strtr(file_get_contents($this->getPackagesPath($file)), self::REPLACE_LINE_BREAKS);
        $contentFromVendor = strtr(file_get_contents($this->getVendorPath($file)), self::REPLACE_LINE_BREAKS);
        return $contentFromPackages === $contentFromVendor;
    }

    protected function assertMergePlan(array $expected): void
    {
        $this->assertSame($expected, require $this->getPackagesPath(self::MERGE_PLAN_FILE));
    }

    protected function assertOutputMessages(string $expected): void
    {
        $this->assertSame($expected, Helper::removeDecoration((new ConsoleOutput())->getFormatter(), $this->output));
    }

    /**
     * @return IOInterface|MockObject
     */
    protected function createIoMock()
    {
        $mock = $this->getMockBuilder(IOInterface::class)
            ->onlyMethods(['select', 'askConfirmation', 'isInteractive', 'write'])
            ->getMockForAbstractClass()
        ;

        $mock->method('write')->willReturnCallback(fn (string $message) => $this->output .= "$message\n");
        return $mock;
    }
}
