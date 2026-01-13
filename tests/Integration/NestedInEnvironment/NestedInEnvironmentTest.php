<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NestedInEnvironment;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NestedInEnvironmentTest extends IntegrationTestCase
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
                    'params' => 'params.php',
                    'definitions' => 'definitions.php',
                    'definitions-backend' => [
                        '$definitions',
                        'definitions-backend.php',
                    ],
                ],
                'config-plugin-environments' => [
                    'environment' => [
                        'definitions' => 'environment/definitions.php',
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: 'environment',
        );

        $this->assertSame(
            [
                'app-base' => 7,
                'only-app' => 42,
                'env-base' => 8,
                'app-backend' => 2,
            ],
            $config->get('definitions-backend'),
        );
    }
}
