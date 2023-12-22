<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Modifier;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Modifier\RemoveFromVendor;

final class RemoveKeysFromVendorTest extends TestCase
{
    public function testBase()
    {
        $modifier = RemoveFromVendor::keys(['app'])->package('test/a', 'test/b');

        $this->assertSame([['app']], $modifier->getKeys());
        $this->assertSame([['test/a', 'test/b']], $modifier->getPackages());
    }

    public function testInvalidPackage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package should be in format "packageName[, group][, group]".');
        RemoveFromVendor::keys(['app'])->package();
    }
}
