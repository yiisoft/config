<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DuplicateRootKeys;

use ErrorException;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DuplicateRootKeysTest extends IntegrationTestCase
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
                    'params' => 'params/*.php',
                ],
            ],
            configDirectory: 'config',
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
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
                    'params' => 'params/*.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [ReverseMerge::groups('params')],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n" .
            ' - config/params/a.php' . "\n" .
            ' - config/params/b.php'
        );
        $config->get('params');
    }
}
