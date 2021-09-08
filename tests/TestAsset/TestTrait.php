<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\TestAsset;

use function str_replace;
use function strtr;

trait TestTrait
{
    /**
     * Asserting same ignoring slash.
     */
    private function assertSameIgnoringSlash(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            str_replace(['/', '\\'], '/', $expected),
            str_replace(['/', '\\'], '/', $actual),
            $message,
        );
    }

    /**
     * Asserts that two strings equality ignoring line endings.
     */
    private function assertEqualStringsIgnoringLineEndings(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            strtr($expected, ["\r\n" => "\n", "\r" => "\n"]),
            strtr($actual, ["\r\n" => "\n", "\r" => "\n"]),
            $message,
        );
    }
}
