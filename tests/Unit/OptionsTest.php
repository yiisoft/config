<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Options;

final class OptionsTest extends TestCase
{
    public function testSilentOverride(): void
    {
        $options = new Options([]);
        $this->assertFalse($options->silentOverride());

        $options = new Options([
            'config-plugin-options' => [
                'silent-override' => true,
            ],
        ]);
        $this->assertTrue($options->silentOverride());

        $options = new Options([
            'config-plugin-options' => [
                'silent-override' => false,
            ],
        ]);
        $this->assertFalse($options->silentOverride());
    }

    public function testDefaultOutputDirectory(): void
    {
        $options = new Options([]);
        $this->assertSame(Options::DEFAULT_CONFIGS_DIRECTORY, $options->outputDirectory());
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
    public function testOutputDirectory(string $expected, string $path): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'output-directory' => $path,
            ],
        ]);
        $this->assertSame($expected, $options->outputDirectory());
    }

    public function testDefaultSourceDirectory(): void
    {
        $options = new Options([]);
        $this->assertSame('', $options->sourceDirectory());
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

    public function testExtraOptionsNotArray(): void
    {
        $options = new Options([
            'config-plugin-options' => true,
        ]);
        $this->assertFalse($options->silentOverride());
        $this->assertSame('', $options->sourceDirectory());
        $this->assertSame(Options::DEFAULT_CONFIGS_DIRECTORY, $options->outputDirectory());
    }
}
