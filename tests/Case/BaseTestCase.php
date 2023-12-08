<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Case;

use Composer\Console\Application;
use Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

abstract class BaseTestCase extends TestCase
{
    protected ?string $rootPath = null;
    protected string $mergePlanPath = '/.merge-plan.php';
    protected string $vendorPath = '/vendor';

    protected function setUp(): void
    {
        $this->rootPath = null;
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
        }
        parent::tearDown();
    }

    protected function prepareConfig(
        string $rootPath,
        array $packages = [],
        ?array $configuration = null,
    ): Config {
        $this->rootPath = $rootPath;

        $this->createComposerJson($rootPath, $packages, $configuration);

        $application = new Application();
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'update',
            '--working-dir' => $rootPath,
            '--no-interaction' => true,
        ]);

        $output = new BufferedOutput();

        try {
            $application->run($input, $output);
            return new Config(
                new ConfigPaths($rootPath)
            );
        } catch (Throwable $exception) {
            echo $output->fetch();
            throw $exception;
        }
    }

    private function createComposerJson(string $rootPath, array $packages, ?array $configuration): void
    {
        $require = ["yiisoft/config"];
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
            fn($path) => '{"type":"path","url":"' . $path . '"}',
            $repositories
        );

        $extraItems = [];
        if ($configuration !== null) {
            $extraItems = ['config-plugin' => $configuration];
        }

        $composerJson = strtr(
            file_get_contents(__DIR__ . '/composer.json.tpl'),
            [
                '%REQUIRE%' => implode(', ', $requireItems),
                '%REPOSITORIES%' => empty($repositoriesItems) ? '' : (implode(', ', $repositoriesItems) . ','),
                '%EXTRA%' => json_encode($extraItems),
            ]
        );

        file_put_contents($rootPath . '/composer.json', $composerJson);
    }
}
