<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Options;

final class OptionsTest extends TestCase
{
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

    public function testDefaultSourceDirectory(): void
    {
        $options = new Options([]);
        $this->assertSame(Options::DEFAULT_CONFIGS_DIRECTORY, $options->sourceDirectory());
    }

    public function testExtraOptionsNotArray(): void
    {
        $options = new Options([
            'config-plugin-options' => true,
        ]);
        $this->assertSame(Options::DEFAULT_CONFIGS_DIRECTORY, $options->sourceDirectory());
    }
}
