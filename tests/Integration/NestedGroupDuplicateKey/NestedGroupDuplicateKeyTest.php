<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NestedGroupDuplicateKey;

use ErrorException;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NestedGroupDuplicateKeyTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin' => [
                    'params' => [],
                    'di' => 'di.php',
                    'di-web' => [
                        '$di',
                        'di-web.php',
                    ],
                ],
            ],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            'Duplicate key "key" in the following configs while building "di-web" group:' . "\n" .
            ' - di-web.php' . "\n" .
            ' - di.php'
        );
        $config->get('di-web');
    }
}
