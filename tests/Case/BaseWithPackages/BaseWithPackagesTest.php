<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Case\BaseWithPackages;

use Yiisoft\Config\Tests\Case\BaseTestCase;

final class BaseWithPackagesTest extends BaseTestCase
{
    public function testBase(): void
    {
        $config = $this->prepareConfig(__DIR__);

        $this->assertSame(['a' => 1, 'b' => 2], $config->get('params'));
        $this->assertSame([], $config->get('web'));
    }
}
