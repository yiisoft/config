<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\EmptyGroup;

use Yiisoft\Config\Tests\Integration\BaseTestCase;

final class EmptyGroupTest extends BaseTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => [],
                    'widgets' => [],
                ],
            ],
        );

        $this->assertSame([], $config->get('params'));
        $this->assertSame([], $config->get('widgets'));
    }
}
