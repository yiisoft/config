<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\RecursiveReverse;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RecursiveReverseTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/b' => __DIR__ . '/packages/b',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups('params'),
                ReverseMerge::groups('params'),
            ],
        );

        $this->assertSame(
            [
                'array' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
                'nested' => [
                    'nested-key' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
                ],
            ],
            $config->get('params')
        );
    }

    public function testRemoveNestedKeyFromVendor(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/b' => __DIR__ . '/packages/b',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups('params'),
                ReverseMerge::groups('params'),
                RemoveFromVendor::keys(['nested', 'nested-key']),
            ],
        );

        $this->assertSame(
            [
                'array' => [7, 8, 9, 4, 5, 6, 1, 2, 3],
                'nested' => [
                    'nested-key' => [7, 8, 9],
                ],
            ],
            $config->get('params')
        );
    }
}
