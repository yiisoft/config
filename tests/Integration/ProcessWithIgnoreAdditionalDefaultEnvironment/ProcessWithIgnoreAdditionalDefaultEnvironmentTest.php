<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\ProcessWithIgnoreAdditionalDefaultEnvironment;

use Yiisoft\Config\Composer\Options;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class ProcessWithIgnoreAdditionalDefaultEnvironmentTest extends IntegrationTestCase
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
                ],
                'config-plugin-environments' => [
                    'alfa' => [
                        'params' => 'alfa/params.php',
                        'web' => 'alfa/web.php',
                        'main' => [
                            '$web',
                            'alfa/main.php',
                        ],
                    ],
                    Options::DEFAULT_ENVIRONMENT => [
                        'params' => 'params.php',
                        'web' => 'web.php',
                        'main' => [
                            '$web',
                            'main.php',
                        ],
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: 'alfa',
        );

        $this->assertSame(
            [
                'root-params-key' => 'root-params-value',
                'alfa-params-key' => 'alfa-params-value',
            ],
            $config->get('params'),
        );
        $this->assertSame(
            [
                'alfa-web-key' => 'alfa-web-value',
            ],
            $config->get('web'),
        );
        $this->assertSame(
            [
                'alfa-web-key' => 'alfa-web-value',
                'alfa-main-key' => 'alfa-main-value',
                'a-web-environment-override-key' => 'alfa-web-override-value',
                'b-web-environment-override-key' => 'alfa-web-override-value',
                'c-web-environment-override-key' => 'alfa-web-override-value',
            ],
            $config->get('main'),
        );
    }
}
