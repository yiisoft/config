<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Yiisoft\Config\Options;

use function array_merge;
use function dirname;
use function file_get_contents;
use function json_decode;
use function putenv;
use function str_replace;
use function sys_get_temp_dir;
use function trim;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private Filesystem $filesystem;
    private string $sourceDirectory;
    private string $tempDirectory;
    private string $tempConfigsDirectory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->filesystem = new Filesystem();
        $this->sourceDirectory = dirname(__DIR__) . '/TestAsset/packages';
        $this->tempDirectory = sys_get_temp_dir() . '/yiisoft';
        $this->tempConfigsDirectory = "$this->tempDirectory/config";
    }

    protected function setUp(): void
    {
        $this->filesystem->ensureDirectoryExists($this->tempConfigsDirectory);
        putenv("COMPOSER=$this->tempDirectory/composer.json");
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->filesystem->removeDirectory($this->tempDirectory);
    }

    protected function getSourcePath(string $path = ''): string
    {
        return $this->sourceDirectory . (empty($path) ? '' : '/' . trim($path, '/'));
    }

    protected function getTempPath(string $path = ''): string
    {
        return $this->tempConfigsDirectory . (empty($path) ? '' : '/' . trim($path, '/'));
    }

    protected function assertMergePlan(array $environments = []): void
    {
        $this->assertSame(
            array_merge([
                Options::DEFAULT_ENVIRONMENT => [
                    'params' => [
                        'test/a' => [
                            'params.php',
                        ],
                        'test/c' => [
                            'config/params.php',
                        ],
                        'test/custom-source' => [
                            'custom-dir/params.php',
                        ],
                        Options::VENDOR_OVERRIDE_PACKAGE_NAME => [
                            'test/over/params.php',
                        ],
                        Options::ROOT_PACKAGE_NAME => [
                            'params.php',
                            '?params-local.php',
                        ],
                    ],
                    'web' => [
                        'test/a' => [
                            'web.php',
                        ],
                        'test/ba' => [
                            'config/web.php',
                        ],
                        'test/c' => [
                            'config/web.php',
                        ],
                        'test/custom-source' => [
                            'custom-dir/web.php',
                        ],
                        'test/d-dev-c' => [
                            'config/web.php',
                        ],
                        Options::VENDOR_OVERRIDE_PACKAGE_NAME => [
                            'test/over/web.php',
                        ],
                        Options::ROOT_PACKAGE_NAME => [
                            '$common',
                            'web.php',
                        ],
                    ],
                    'common' => [
                        'test/custom-source' => [
                            'custom-dir/common/a.php',
                            'custom-dir/common/b.php',
                        ],
                        Options::ROOT_PACKAGE_NAME => [
                            'common/*.php',
                        ],
                    ],
                    'events' => [
                        'test/custom-source' => [
                            'custom-dir/events.php',
                        ],
                    ],
                    'events-web' => [
                        'test/custom-source' => [
                            '$events',
                            'custom-dir/events-web.php',
                        ],
                    ],
                    'empty' => [
                        Options::ROOT_PACKAGE_NAME => [],
                    ],
                ],
            ], $environments),
            require $this->getTempPath(Options::MERGE_PLAN_FILENAME),
        );
    }

    protected function getInaccessibleProperty(object $object, string $propertyName)
    {
        $class = new ReflectionClass($object);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);
        $property->setAccessible(false);
        return $result;
    }

    protected function setInaccessibleProperty(object $object, string $propertyName, $value): void
    {
        $property = (new ReflectionClass($object))->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
    }

    /**
     * @return IOInterface|MockObject
     */
    protected function createIoMock()
    {
        return $this
            ->getMockBuilder(IOInterface::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return Composer|MockObject
     */
    protected function createComposerMock(
        array $extraEnvironments = [],
        array $vendorOverridePackage = null,
        bool $buildMergePlan = true,
        string $extraConfigFile = null
    ) {
        $rootPath = $this->tempDirectory;
        $sourcePath = $this->sourceDirectory;
        $targetPath = "$this->tempDirectory/vendor";

        $extra = array_merge([
            'config-plugin-file' => $extraConfigFile,
            'config-plugin-options' => [
                'source-directory' => 'config',
                'vendor-override-layer' => $vendorOverridePackage ?? 'test/over',
                'build-merge-plan' => $buildMergePlan,
            ],
            'config-plugin' => [
                'empty' => [],
                'common' => 'common/*.php',
                'params' => [
                    'params.php',
                    '?params-local.php',
                ],
                'web' => [
                    '$common',
                    'web.php',
                ],
            ],
        ], ['config-plugin-environments' => $extraEnvironments]);

        $config = $this->createMock(Config::class);
        $config
            ->method('get')
            ->willReturn(dirname(__DIR__, 2) . '/vendor');

        $rootPackage = $this
            ->getMockBuilder(RootPackageInterface::class)
            ->onlyMethods(['getRequires', 'getDevRequires', 'getExtra'])
            ->getMockForAbstractClass()
        ;
        $rootPackage
            ->method('getRequires')
            ->willReturn([
                'test/a' => new Link("$sourcePath/a", "$targetPath/test/a", new Constraint('>=', '1.0.0')),
                'test/ba' => new Link("$sourcePath/ba", "$targetPath/test/ba", new Constraint('>=', '1.0.0')),
                'test/c' => new Link("$sourcePath/c", "$targetPath/test/c", new Constraint('>=', '1.0.0')),
                'test/custom-source' => new Link("$sourcePath/custom-source", "$targetPath/test/custom-source", new Constraint('>=', '1.0.0')),
                'test/over' => new Link("$sourcePath/over", "$targetPath/test/over", new Constraint('>=', '1.0.0')),
            ]);
        $rootPackage
            ->method('getDevRequires')
            ->willReturn([
                'test/d-dev-c' => new Link("$sourcePath/d-dev-c", "$targetPath/test/d-dev-c", new Constraint('>=', '1.0.0')),
            ]);
        $rootPackage
            ->method('getExtra')
            ->willReturn($extra);

        $packages = [
            new CompletePackage('test/a', '1.0.0', '1.0.0'),
            new CompletePackage('test/ba', '1.0.0', '1.0.0'),
            new CompletePackage('test/c', '1.0.0', '1.0.0'),
            new CompletePackage('test/custom-source', '1.0.0', '1.0.0'),
            new CompletePackage('test/d-dev-c', '1.0.0', '1.0.0'),
            new CompletePackage('test/over', '1.0.0', '1.0.0'),
            new Package('test/e', '1.0.0', '1.0.0'),
        ];

        foreach ($packages as $package) {
            $path = str_replace('test/', '', "$sourcePath/{$package->getName()}") . '/composer.json';
            $package->setExtra(json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['extra']);
        }

        $repository = $this
            ->getMockBuilder(InstalledRepositoryInterface::class)
            ->onlyMethods(['getPackages'])
            ->getMockForAbstractClass()
        ;
        $repository
            ->method('getPackages')
            ->willReturn($packages);

        $repositoryManager = $this
            ->getMockBuilder(RepositoryManager::class)
            ->onlyMethods(['getLocalRepository'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $repositoryManager
            ->method('getLocalRepository')
            ->willReturn($repository);

        $installationManager = $this
            ->getMockBuilder(InstallationManager::class)
            ->onlyMethods(['getInstallPath'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $installationManager
            ->method('getInstallPath')
            ->willReturnCallback(
                static function (PackageInterface $package) use ($sourcePath, $rootPath) {
                if ($package instanceof RootPackageInterface) {
                    return $rootPath;
                }
                return str_replace('test/', '', "$sourcePath/{$package->getName()}");
            }
            );

        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->onlyMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $eventDispatcher
            ->method('dispatch')
            ->willReturn(0);

        $composer = $this
            ->getMockBuilder(Composer::class)
            ->onlyMethods([
                'getConfig',
                'getPackage',
                'getRepositoryManager',
                'getInstallationManager',
                'getEventDispatcher',
            ])
            ->getMock()
        ;

        $composer
            ->method('getConfig')
            ->willReturn($config);
        $composer
            ->method('getPackage')
            ->willReturn($rootPackage);
        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager);
        $composer
            ->method('getInstallationManager')
            ->willReturn($installationManager);
        $composer
            ->method('getEventDispatcher')
            ->willReturn($eventDispatcher);

        return $composer;
    }
}
