<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Case\BaseWithPackages;

use Yiisoft\Config\Tests\Case\BaseTestCase;

final class BaseWithPackagesTest extends BaseTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'web' => [],
                ],
            ],
        );

        $this->assertSame(['a' => 1, 'b' => 2], $config->get('params'));
        $this->assertSame([], $config->get('web'));
    }
}
