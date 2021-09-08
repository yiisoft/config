<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Yiisoft\Config\Composer\PackagesListBuilder;

final class PackagesListBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $packages = (new PackagesListBuilder($this->createComposerMock()))->build();

        $this->assertCount(5, $packages);
        $this->assertSame('test/a', $packages['test/a']->getPrettyName());
        $this->assertSame('test/ba', $packages['test/ba']->getPrettyName());
        $this->assertSame('test/c', $packages['test/c']->getPrettyName());
        $this->assertSame('test/custom-source', $packages['test/custom-source']->getPrettyName());
        $this->assertSame('test/d-dev-c', $packages['test/d-dev-c']->getPrettyName());
    }
}
