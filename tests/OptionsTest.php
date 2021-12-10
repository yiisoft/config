<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Options;

final class OptionsTest extends TestCase
{
    public function buildMergePlanDataProvider(): array
    {
        return [
            'true' => [true],
            'int' => [1],
            'string' => ['yes'],
            'string-int' => ['1'],
            'array' => [['']],
        ];
    }

    /**
     * @dataProvider buildMergePlanDataProvider
     *
     * @param mixed $value
     */
    public function testBuildMergePlanTrue($value): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'build-merge-plan' => $value,
            ],
        ]);
        $this->assertTrue($options->buildMergePlan());
    }

    public function noBuildMergePlanDataProvider(): array
    {
        return [
            'false' => [false],
            'int' => [0],
            'string-int' => ['0'],
            'empty-string' => [''],
            'empty-array' => [[]],
        ];
    }

    /**
     * @dataProvider noBuildMergePlanDataProvider
     *
     * @param mixed $value
     */
    public function testBuildMergePlanFalse($value): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'build-merge-plan' => $value,
            ],
        ]);
        $this->assertFalse($options->buildMergePlan());
    }

    public function directoryDataProvider(): array
    {
        return [
            ['', ''],
            ['', '/'],
            ['', '//'],
            ['', '\\'],
            ['custom/dir', 'custom/dir'],
            ['custom/dir', '/custom/dir'],
            ['custom/dir', '/custom/dir/'],
            ['custom/dir', '//custom/dir//'],
            ['custom/dir', '\\custom\\dir\\'],
        ];
    }

    /**
     * @dataProvider directoryDataProvider
     */
    public function testSourceDirectory(string $expected, string $path): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'source-directory' => $path,
            ],
        ]);
        $this->assertSame($expected, $options->sourceDirectory());
    }

    public function configurationFileDataProvider(): array
    {
        return [
            ['file.php', '/file.php'],
            ['file.php', '//file.php'],
            ['file.php', '\\file.php'],
            ['dir/file.php', 'dir/file.php'],
            ['dir/file.php', '/dir/file.php'],
            ['dir/file.php', '//dir/file.php'],
            ['dir/file.php', '\\dir\\file.php'],
        ];
    }

    /**
     * @dataProvider configurationFileDataProvider
     */
    public function testRootConfigurationFile(string $expected, string $path): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'root-configuration-file' => $path,
            ],
        ]);
        $this->assertSame($expected, $options->rootConfigurationFile());
    }

    /**
     * @dataProvider configurationFileDataProvider
     */
    public function testEnvironmentConfigurationFile(string $expected, string $path): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'environment-configuration-file' => $path,
            ],
        ]);
        $this->assertSame($expected, $options->environmentConfigurationFile());
    }

    public function testDefaultOptions(): void
    {
        $options = new Options([]);
        $this->assertTrue($options->buildMergePlan());
        $this->assertNull($options->rootConfigurationFile());
        $this->assertNull($options->environmentConfigurationFile());
        $this->assertSame(Options::DEFAULT_CONFIG_DIRECTORY, $options->sourceDirectory());
    }

    public function testExtraOptionsNotArray(): void
    {
        $options = new Options([
            'config-plugin-options' => true,
        ]);
        $this->assertTrue($options->buildMergePlan());
        $this->assertSame(Options::DEFAULT_CONFIG_DIRECTORY, $options->sourceDirectory());
    }
}
