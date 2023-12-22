<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\Events;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class EventsTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $eventGroups = ['events', 'events-console'];
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
                    'events' => 'events.php',
                    'events-console' => [
                        '$events',
                        'events-console.php',
                    ],
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups(...$eventGroups),
                ReverseMerge::groups(...$eventGroups),
            ],
        );

        $this->assertSame(
            [
                'e1' => [
                    ['app3', 'handler1'],
                    ['app1', 'handler1'],
                    ['package-b1', 'handler1'],
                    ['package-a1', 'handler1'],
                    ['package-a2', 'handler1'],
                    ['package-a3', 'handler1'],
                ],
                'e2' => [
                    ['app2', 'handler2'],
                    ['package-b2', 'handler1'],
                ],
            ],
            $config->get('events-console')
        );
    }

    public function testReverseAndRemoveFromVendor(): void
    {
        $eventGroups = ['events', 'events-console'];
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
                    'events' => 'events.php',
                    'events-console' => [
                        '$events',
                        'events-console.php',
                    ],
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups(...$eventGroups),
                ReverseMerge::groups(...$eventGroups),
                RemoveFromVendor::keys(['e2']),
            ],
        );

        $this->assertSame(
            [
                'e1' => [
                    ['app3', 'handler1'],
                    ['app1', 'handler1'],
                    ['package-b1', 'handler1'],
                    ['package-a1', 'handler1'],
                    ['package-a2', 'handler1'],
                    ['package-a3', 'handler1'],
                ],
                'e2' => [
                    ['app2', 'handler2'],
                ],
            ],
            $config->get('events-console')
        );
    }
}
