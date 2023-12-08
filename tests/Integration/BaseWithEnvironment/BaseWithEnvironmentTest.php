<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\BaseWithEnvironment;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class BaseWithEnvironmentTest extends IntegrationTestCase
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
                    'params' => 'params.php',
                    'web' => [],
                ],
                'config-plugin-environments' => [
                    'dev' => [
                        'params' => 'params-dev.php',
                    ],
                ],
            ],
            environment: 'dev',
        );

        $this->assertSame(
            [
                'a' => 1,
                'b' => 99,
                'c' => 3,
            ],
            $config->get('params')
        );
    }

    public function testEmptyEnvironment(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'web' => [],
                ],
                'config-plugin-environments' => [
                    'dev' => [],
                ],
            ],
            environment: 'dev',
        );

        $this->assertSame(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ],
            $config->get('params')
        );
    }
}
