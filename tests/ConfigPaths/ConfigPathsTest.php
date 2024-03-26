<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\ConfigPaths;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Composer\Options;

final class ConfigPathsTest extends TestCase
{
    public function absolutePathsDataProvider(): array
    {
        return [
            ['params.php', Options::ROOT_PACKAGE_NAME, __DIR__ . '/config/params.php'],
            ['alfa/main.php', Options::ROOT_PACKAGE_NAME,__DIR__ . '/config/alfa/main.php'],
            ['common.php', 'package/a', __DIR__ . '/vendor/package/a/common.php'],
            ['web.php', 'package/b', __DIR__ . '/vendor/package/b/web.php'],
        ];
    }

    /**
     * @dataProvider absolutePathsDataProvider
     */
    public function testAbsolute(string $file, string $package, string $expected): void
    {
        $this->assertSame(
            $expected,
            $this->createConfigPaths()->absolute($file, $package),
        );
    }

    private function createConfigPaths(): ConfigPaths
    {
        return new ConfigPaths(__DIR__, 'config', 'vendor');
    }
}
