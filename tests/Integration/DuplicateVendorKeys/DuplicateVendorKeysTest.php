<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DuplicateVendorKeys;

use ErrorException;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DuplicateVendorKeysTest extends IntegrationTestCase
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
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n"
            . ' - vendor/test/a/params.php' . "\n"
            . ' - vendor/test/b/params.php',
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
                    'params' => 'params.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [ReverseMerge::groups('params')],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "age" in the following configs while building "params" group:' . "\n"
            . ' - vendor/test/a/params.php' . "\n"
            . ' - vendor/test/b/params.php',
        );
        $config->get('params');
    }
}
