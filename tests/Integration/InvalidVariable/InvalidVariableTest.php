<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\InvalidVariable;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class InvalidVariableTest extends IntegrationTestCase
{
    public function testNotExist(): void
    {
        $output = $this->runComposerUpdate(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'params-web' => '$unknown',
                ],
            ],
        );

        $this->assertStringContainsString('The "unknown" configuration group does not exist.', $output);
    }

    public function testCircularDependency(): void
    {
        $output = $this->runComposerUpdate(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'params1' => '$params2',
                    'params2' => '$params3',
                    'params3' => '$params1',
                ],
            ],
        );

        $this->assertStringContainsString('Circular references in configuration.', $output);
    }
}
