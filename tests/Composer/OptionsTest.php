<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Composer\Options;

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
     */
    public function testBuildMergePlanTrue(mixed $value): void
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
     */
    public function testBuildMergePlanFalse(mixed $value): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'build-merge-plan' => $value,
            ],
        ]);
        $this->assertFalse($options->buildMergePlan());
    }

    public function packagePatternDataProvider(): array
    {
        return [
            'string' => ['vendor-name/*'],
            'array' => [['vendor-1/package-name', 'vendor-2/package-name', 'vendor-3/*']],
        ];
    }

    /**
     * @dataProvider packagePatternDataProvider
     */
    public function testVendorOverrideLayerPackages(array|string $packages): void
    {
        $options = new Options([
            'config-plugin-options' => [
                'vendor-override-layer' => $packages,
            ],
        ]);

        $this->assertSame((array) $packages, $options->vendorOverrideLayerPackages());
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

    public function testDefaultOptions(): void
    {
        $options = new Options([]);
        $this->assertTrue($options->buildMergePlan());
        $this->assertSame([], $options->vendorOverrideLayerPackages());
        $this->assertSame(Options::DEFAULT_CONFIG_DIRECTORY, $options->sourceDirectory());
    }

    public function testExtraOptionsNotArray(): void
    {
        $options = new Options([
            'config-plugin-options' => true,
        ]);
        $this->assertTrue($options->buildMergePlan());
        $this->assertSame([], $options->vendorOverrideLayerPackages());
        $this->assertSame(Options::DEFAULT_CONFIG_DIRECTORY, $options->sourceDirectory());
    }
}
