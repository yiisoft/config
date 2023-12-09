<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\ConfigurationFile;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class ConfigurationFileTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin-file' => 'configuration.php',
            ],
        );

        $this->assertSame(['a' => 1, 'c' => 3], $config->get('params'));
        $this->assertSame(['b' => 2], $config->get('web'));
    }
}
