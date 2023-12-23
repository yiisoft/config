<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DuplicateEnvironmentKeys;

use ErrorException;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DuplicateEnvironmentKeysTest extends IntegrationTestCase
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
                        'params/*.php',
                        ['environment', 'environment/params/*.php'],
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: 'environment',
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/environment/params/a.php' . "\n" .
            ' - config/environment/params/b.php'
        );
        $config->get('params');
    }

    public function testWithReverseMerge(): void
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
                        'params/*.php',
                        ['environment', 'environment/params/*.php'],
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: 'environment',
            modifiers: [ReverseMerge::groups('params')],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/environment/params/a.php' . "\n" .
            ' - config/environment/params/b.php'
        );
        $config->get('params');
    }
}
