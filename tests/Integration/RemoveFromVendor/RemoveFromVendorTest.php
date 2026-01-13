<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\RemoveFromVendor;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RemoveFromVendorTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
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
                RemoveFromVendor::keys(['nested', 'nested2']),
            ],
        );

        $this->assertSame(
            [
                'nested' => [
                    'nested3' => 3,
                    'nested1' => 1,
                ],
                'app' => 42,
            ],
            $config->get('params'),
        );
    }

    public function testWithReverse(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
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
                RemoveFromVendor::keys(['nested', 'nested2']),
            ],
        );

        $this->assertSame(
            [
                'app' => 42,
                'nested' => [
                    'nested1' => 1,
                    'nested3' => 3,
                ],
            ],
            $config->get('params'),
        );
    }
}
