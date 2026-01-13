<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\DoubleDotInPath;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DoubleDotInPathTest extends IntegrationTestCase
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
                    'params' => [
                        'params.php',
                        '../src/Module/config/params.php',
                    ],
                ],
            ],
            configDirectory: 'config',
        );

        $this->assertSame(
            [
                'a' => 1,
                'b' => 2,
            ],
            $config->get('params'),
        );
    }
}
