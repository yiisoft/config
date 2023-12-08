<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use Composer\Console\Application;
use Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;
use Yiisoft\Config\Command\ConfigCommandProvider;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Options;

abstract class IntegrationTestCase extends TestCase
{
    protected ?string $rootPath = null;
    protected ?string $mergePlanPath = null;
    protected string $vendorPath = '/vendor';
    protected array $removeFiles = [];
    protected array $removeDirectories = [];

    protected function setUp(): void
    {
        $this->rootPath = null;
        $this->mergePlanPath = null;
        $this->removeFiles = [];
        $this->removeDirectories = [];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->rootPath !== null) {
            $filesystem = new Filesystem();
            $filesystem->removeDirectory($this->rootPath . $this->vendorPath);
            $filesystem->remove($this->rootPath . '/composer.json');
            $filesystem->remove($this->rootPath . '/composer.lock');
            $filesystem->remove($this->rootPath . $this->mergePlanPath);
            foreach ($this->removeFiles as $file) {
                $filesystem->remove($file);
            }
            foreach ($this->removeDirectories as $directory) {
                $filesystem->removeDirectory($directory);
            }
        }
        parent::tearDown();
    }

    public function runComposerYiiConfigCopy(
        string $rootPath,
        array $arguments,
        array $packages = [],
        array $extra = [],
        ?string $configDirectory = null,
        string $mergePlanFile = Options::DEFAULT_MERGE_PLAN_FILE,
    ): string {
        $this->runComposerUpdate(
            $rootPath,
            $packages,
            $extra,
            $configDirectory,
            $mergePlanFile
        );

        return $this->runComposerCommand(
            array_merge(
                ['command' => 'yii-config-copy'],
                $arguments,
            ),
            $rootPath,
            $packages,
            $extra,
            $configDirectory,
            $mergePlanFile
        );
    }

    public function runComposerUpdate(
        string $rootPath,
        array $packages = [],
        array $extra = [],
        ?string $configDirectory = null,
        string $mergePlanFile = Options::DEFAULT_MERGE_PLAN_FILE,
    ): string {
        return $this->runComposerCommand(
            ['command' => 'update'],
            $rootPath,
            $packages,
            $extra,
            $configDirectory,
            $mergePlanFile
        );
    }

    protected function runComposerUpdateAndCreateConfig(
        string $rootPath,
        array $packages = [],
        array $extra = [],
        ?string $configDirectory = null,
        string $mergePlanFile = Options::DEFAULT_MERGE_PLAN_FILE,
        ?string $environment = null,
    ): Config {
        $output = $this->runComposerUpdate($rootPath, $packages, $extra, $configDirectory, $mergePlanFile);

        try {
            return new Config(
                new ConfigPaths($rootPath, $configDirectory),
                environment: $environment,
                mergePlanFile: $mergePlanFile,
            );
        } catch (Throwable $exception) {
            echo $output;
            throw $exception;
        }
    }

    protected function runComposerCommand(
        array $arguments,
        string $rootPath,
        array $packages = [],
        array $extra = [],
        ?string $configDirectory = null,
        string $mergePlanFile = Options::DEFAULT_MERGE_PLAN_FILE,
    ): string {
        $this->rootPath = $rootPath;
        $this->mergePlanPath = '/' . ($configDirectory === null ? '' : ($configDirectory . '/')) . $mergePlanFile;

        $this->createComposerJson($rootPath, $packages, $extra);

        $application = new Application();
        $application->addCommands((new ConfigCommandProvider())->getCommands());
        $application->setAutoExit(false);

        $input = new ArrayInput(
            array_merge(
                [
                    '--working-dir' => $rootPath,
                    '--no-interaction' => true,
                ],
                $arguments,
            ),
        );

        $output = new BufferedOutput();

        try {
            $application->run($input, $output);
        } catch (Throwable $exception) {
            echo $output->fetch();
            throw $exception;
        }

        return $output->fetch();
    }

    private function createComposerJson(string $rootPath, array $packages, array $extra): void
    {
        $require = ['yiisoft/config'];
        $repositories = [];

        foreach ($packages as $name => $path) {
            $require[] = $name;
            $repositories[] = $path;
        }

        $requireItems = array_map(
            fn($package) => '"' . $package . '": "*"',
            $require
        );

        $repositoriesItems = array_map(
            fn($path) => '{"type":"path","url":"' . str_replace('\\', '/', $path) . '"}',
            $repositories
        );

        $composerJson = strtr(
            file_get_contents(__DIR__ . '/composer.json.tpl'),
            [
                '%REQUIRE%' => implode(', ', $requireItems),
                '%REPOSITORIES%' => empty($repositoriesItems) ? '' : (implode(', ', $repositoriesItems) . ','),
                '%EXTRA%' => json_encode($extra),
            ]
        );

        file_put_contents($rootPath . '/composer.json', $composerJson);
    }
}
