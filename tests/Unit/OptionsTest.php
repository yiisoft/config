<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Config\Exception\IncorrectOutputDirectoryOptionException;
use Yiisoft\Config\Exception\IncorrectSilentOverrideOptionException;
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

    public function dataIncorrectSilentOverride(): array
    {
        return [
            ['string'],
            [42],
            [new stdClass()],
            [7.92]
        ];
    }

    /**
     * @dataProvider dataIncorrectSilentOverride
     */
    public function testIncorrectSilentOverride($value): void
    {
        $this->expectException(IncorrectSilentOverrideOptionException::class);
        new Options([
            'config-plugin-options' => [
                'silent-override' => $value,
            ],
        ]);
    }

    public function testOutputDirectory(): void
    {
        $options = new Options([]);
        $this->assertSame('config/packages', $options->outputDirectory());

        $options = new Options([
            'config-plugin-options' => [
                'output-directory' => 'custom-dir-packages',
            ],
        ]);
        $this->assertSame('custom-dir-packages', $options->outputDirectory());
    }

    public function dataIncorrectOutputDirectory(): array
    {
        return [
            [''],
            [42],
            [new stdClass()],
            [7.92],
        ];
    }

    /**
     * @dataProvider dataIncorrectOutputDirectory
     */
    public function testIncorrectOutputDirectory($value): void
    {
        $this->expectException(IncorrectOutputDirectoryOptionException::class);
        new Options([
            'config-plugin-options' => [
                'output-directory' => $value,
            ],
        ]);
    }
}
