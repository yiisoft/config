<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\Recursive;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RecursiveTest extends IntegrationTestCase
{
    public function testRemoveFromVendor(): void
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
                RemoveFromVendor::keys(
                    ['b-params-key'],
                    ['array'],
                    ['nested', 'a']
                ),
            ],
        );

        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'nested' => [
                    'a' => [1],
                    'b' => 2,
                ],
                'root-params-key' => 'root-params-value',
                'array' => [7, 8, 9],
            ],
            $config->get('params')
        );
    }
}
