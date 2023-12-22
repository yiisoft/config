<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\MergeIndexedArray;

use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class MergeIndexedArrayTest extends IntegrationTestCase
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
                'app' => [
                    'vendor-package-a',
                    'vendor-package-b',
                    'app-1',
                    'app-2',
                ],
            ],
            $config->get('params')
        );
    }
}
