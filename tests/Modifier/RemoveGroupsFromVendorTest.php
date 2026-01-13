<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Modifier;

use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Modifier\RemoveFromVendor;

final class RemoveGroupsFromVendorTest extends TestCase
{
    public function testBase(): void
    {
        $modifier = RemoveFromVendor::groups([
            '*' => 'params',
            'yiisoft/view' => ['params', 'common'],
        ]);

        $this->assertSame(
            [
                '*' => ['params'],
                'yiisoft/view' => ['params', 'common'],
            ],
            $modifier->getGroups(),
        );
    }
}
