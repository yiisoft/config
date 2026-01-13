<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DeepRecursive;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DeepRecursiveTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => [
                        'params1.php',
                        'params2.php',
                    ],
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groups('params'),
            ],
        );

        $this->assertSame(
            [
                'nested' => [
                    'nested2' => [
                        'nested3-1' => 1,
                        'nested3-2' => 2,
                        'nested3-3' => 3,
                        'nested3-4' => 4,
                    ],
                ],
            ],
            $config->get('params'),
        );
    }
}
