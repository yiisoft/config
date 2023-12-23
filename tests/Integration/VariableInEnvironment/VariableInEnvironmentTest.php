<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\VariableInEnvironment;

use ErrorException;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class VariableInEnvironmentTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'params-web' => [
                        ['dev', '$params'],
                    ],
                ],
            ],
            environment: 'dev',
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Don\'t allow to use variables in environments. Found variable "$params".');
        $config->get('params-web');
    }
}
