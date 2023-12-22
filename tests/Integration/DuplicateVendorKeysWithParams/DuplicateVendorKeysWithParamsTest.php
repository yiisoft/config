<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DuplicateVendorKeysWithParams;

use ErrorException;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DuplicateVendorKeysWithParamsTest extends IntegrationTestCase
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
            ],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches(
            '~^Duplicate key "name => first-name" in the following configs while building "params" group~',
        );
        $config->get('params');
    }
}
