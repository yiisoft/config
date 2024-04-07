<?php

declare(strict_types=1);

namespace Integration\InfoCommandTest;

use Yiisoft\Config\Composer\Options;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class InfoCommandTest extends IntegrationTestCase
{
    public function testRootPackage(): void
    {
        [$rootPath, $output] = $this->runInfoCommand();

        $this->assertStringContainsString('Yii Config — Root Configuration', $output);
        $this->assertMatchesRegularExpression('~Build merge plan\s*yes~', $output);
        $this->assertMatchesRegularExpression(
            '~Merge plan file path\s*'
            . preg_quote($rootPath . '/config/' . Options::DEFAULT_MERGE_PLAN_FILE, '~')
            . '~',
            $output
        );
        $this->assertMatchesRegularExpression('~Package types\s*library, composer-plugin~', $output);
        $this->assertMatchesRegularExpression(
            '~Source directory\s*' . preg_quote($rootPath . '/config', '~') . '~',
            $output
        );
        $this->assertMatchesRegularExpression('~Vendor override layer packages\s*not set~', $output);
        $this->assertStringContainsString('Configuration groups', $output);
        $this->assertStringContainsString('- params/*.php', $output);
        $this->assertStringContainsString('Environments', $output);
        $this->assertStringContainsString('environment/params/*.php', $output);
    }

    public function testVendorPackage(): void
    {
        [$rootPath, $output] = $this->runInfoCommand('test/a');

        $this->assertStringContainsString('Yii Config — Package "test/a"', $output);
        $this->assertStringContainsString('Source directory: '.$rootPath.'/vendor/test/a' , $output);
        $this->assertStringContainsString('Configuration groups', $output);
        $this->assertStringContainsString('- params.php', $output);
        $this->assertStringContainsString('- web.php', $output);
    }

    public function testPackageNotFound()
    {
        [, $output] = $this->runInfoCommand('unknown/test');

        $this->assertStringContainsString('Package "unknown/test" not found.', $output);
    }

    public function testPackageWithoutConfiguration()
    {
        [, $output] = $this->runInfoCommand('test/b');

        $this->assertStringContainsString('Configuration don\'t found in package "test/b".', $output);
    }

    private function runInfoCommand(?string $package = null): array
    {
        $rootPath = __DIR__;
        $packages = [
            'test/a' => __DIR__ . '/packages/a',
            'test/b' => __DIR__ . '/packages/b',
        ];
        $extra = [
            'config-plugin-options' => [
                'source-directory' => 'config',
            ],
            'config-plugin' => [
                'params' => 'params/*.php',
            ],
            'config-plugin-environments' => [
                'environment' => [
                    'params' => 'environment/params/*.php',
                ],
            ],
        ];

        $this->runComposerUpdate(
            rootPath: $rootPath,
            packages: $packages,
            extra: $extra,
            configDirectory: 'config',
        );

        $arguments = ['command' => 'yii-config-info'];
        if ($package !== null) {
            $arguments['package'] = $package;
        }
        $output = $this->runComposerCommand(
            $arguments,
            rootPath: $rootPath,
            packages: $packages,
            extra: $extra,
            configDirectory: 'config',
        );

        return [$rootPath, $output];
    }
}
