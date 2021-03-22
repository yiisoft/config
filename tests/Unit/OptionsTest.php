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
}
