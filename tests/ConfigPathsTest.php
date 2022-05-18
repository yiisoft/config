<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Options;
use Yiisoft\Config\Tests\TestAsset\TestTrait;

final class ConfigPathsTest extends TestCase
{
    use TestTrait;

    private const ROOT_PATH = __DIR__ . '/TestAsset/configs/dummy';

    public function absolutePathsDataProvider(): array
    {
        return [
            ['params.php', Options::ROOT_PACKAGE_NAME, self::ROOT_PATH . '/config/params.php'],
            ['alfa/main.php', Options::ROOT_PACKAGE_NAME, self::ROOT_PATH . '/config/alfa/main.php'],
            ['common.php', 'package/a', self::ROOT_PATH . '/vendor/package/a/common.php'],
            ['web.php', 'package/b', self::ROOT_PATH . '/vendor/package/b/web.php'],
        ];
    }

    /**
     * @dataProvider absolutePathsDataProvider
     *
     * @param string $file
     * @param string $package
     * @param string $expected
     */
    public function testAbsolute(string $file, string $package, string $expected): void
    {
        $this->assertSameIgnoringSlash(
            $expected,
            $this
                ->createConfigPaths()
                ->absolute($file, $package),
        );
    }

    private function createConfigPaths(): ConfigPaths
    {
        return new ConfigPaths(self::ROOT_PATH, 'config', 'vendor');
    }
}
