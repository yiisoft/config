<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NestedGroupBug153;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NestedGroupBug153Test extends IntegrationTestCase
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
                    'di-web' => '$di',
                ],
            ],
        );

        $this->assertSame(['key' => 'app-di'], $config->get('di-web'));
    }
}
