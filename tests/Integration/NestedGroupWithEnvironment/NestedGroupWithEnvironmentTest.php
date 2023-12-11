<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NestedGroupWithEnvironment;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NestedGroupWithEnvironmentTest extends IntegrationTestCase
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
                    'params-web' => [
                        '$params',
                        'params-web.php',
                    ],
                ],
                'config-plugin-environments' => [
                    'dev' => [
                        'params' => 'params-dev.php',
                    ],
                ],
            ],
            environment: 'dev',
        );

        $this->assertSame(['key' => 'environment'], $config->get('params'));
        $this->assertSame(['key' => 'environment'], $config->get('params-web'));
    }
}
