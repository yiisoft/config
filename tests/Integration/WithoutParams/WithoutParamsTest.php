<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\WithoutParams;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class WithoutParamsTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/b' => __DIR__ . '/packages/b',
            ],
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                    'web' => 'web.php',
                ],
            ],
            paramsGroup: null,
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'b-web-override-value',
                'root-web-key' => 42,
            ],
            $config->get('web')
        );
    }
}
