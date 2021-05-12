<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yiisoft\Config\Options;

use function array_merge;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function putenv;
use function strtr;
use function sys_get_temp_dir;
use function trim;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private const REPLACE_LINE_BREAKS = ["\r\n" => "\n", "\r" => "\n"];
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
        putenv("COMPOSER=$this->workingDirectory/composer.json");
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

    protected function assertDistLock(array $expected): void
    {
        $this->assertSame(
            $expected,
            json_decode(file_get_contents($this->getPackagesPath(Options::DIST_LOCK_FILENAME)), true),
        );
    }

    protected function assertMergePlan(array $expected): void
    {
        $this->assertSame($expected, require $this->getPackagesPath(Options::MERGE_PLAN_FILENAME));
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

    /**
     * @param array|null $extra
     * @param string|null $customPackageName
     *
     * @return Composer|MockObject
     */
    protected function createComposerMock(array $extra = null, string $customPackageName = null)
    {
        $sourcePath = dirname(__DIR__, 2) . '/tests/Packages';
        $targetPath = dirname(__DIR__, 2) . '/tests/configs';

        $customPackageName ??= 'test/custom-source';
        $extra ??= [
            'config-plugin' => [
                'common' => 'custom-dir/subdir/*.php',
                'params' => [
                    'custom-dir/params.php',
                    '?custom-dir/params-local.php'
                ],
                'web' => [
                    '$common',
                    'custom-dir/web.php'
                ],
            ],
        ];

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturn(dirname(__DIR__, 2) . '/vendor');

        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)
            ->onlyMethods(['getRequires', 'getDevRequires'])
            ->getMockForAbstractClass()
        ;
        $rootPackage->method('getRequires')->willReturn([
            'test/a' => new Link("$sourcePath/test/a", "$targetPath/test/a", new Constraint('>=', '1.0.0')),
            'test/ba' => new Link("$sourcePath/test/ba", "$targetPath/test/ba", new Constraint('>=', '1.0.0')),
            'test/c' => new Link("$sourcePath/test/c", "$targetPath/test/c", new Constraint('>=', '1.0.0')),
            $customPackageName => new Link("$sourcePath/$customPackageName", "$targetPath/$customPackageName", new Constraint('>=', '1.0.0')),
        ]);
        $rootPackage->method('getDevRequires')->willReturn([
            'test/d-dev-c' => new Link("$sourcePath/test/d-dev-c", "$targetPath/test/d-dev-c", new Constraint('>=', '1.0.0')),
        ]);
        $rootPackage->method('getExtra')->willReturn($extra);

        $repository = $this->getMockBuilder(InstalledRepositoryInterface::class)
            ->onlyMethods(['getPackages'])
            ->getMockForAbstractClass()
        ;

        $customPackage = new CompletePackage($customPackageName, '1.0.0', '1.0.0');
        $customPackage->setExtra(array_merge($customPackage->getExtra(), $extra));

        $repository->method('getPackages')->willReturn([
            new CompletePackage('test/a', '1.0.0', '1.0.0'),
            new CompletePackage('test/ba', '1.0.0', '1.0.0'),
            new CompletePackage('test/c', '1.0.0', '1.0.0'),
            $customPackage,
            new CompletePackage('test/d-dev-c', '1.0.0', '1.0.0'),
        ]);

        $repositoryManager = $this->getMockBuilder(RepositoryManager::class)
            ->onlyMethods(['getLocalRepository'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $repositoryManager->method('getLocalRepository')->willReturn($repository);

        $installationManager = $this->getMockBuilder(InstallationManager::class)
            ->onlyMethods(['getInstallPath'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $installationManager->method('getInstallPath')->willReturn("$sourcePath/custom-source");

        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->onlyMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $eventDispatcher->method('dispatch')->willReturn(0);

        $composer = $this->getMockBuilder(Composer::class)
            ->onlyMethods([
                'getConfig',
                'getPackage',
                'getRepositoryManager',
                'getInstallationManager',
                'getEventDispatcher',
            ])
            ->getMock()
        ;

        $composer->method('getConfig')->willReturn($config);
        $composer->method('getPackage')->willReturn($rootPackage);
        $composer->method('getRepositoryManager')->willReturn($repositoryManager);
        $composer->method('getInstallationManager')->willReturn($installationManager);
        $composer->method('getEventDispatcher')->willReturn($eventDispatcher);

        return $composer;
    }
}
