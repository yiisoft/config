<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class PackagesListBuilderWithDevTest extends ComposerTest
{
    public function testBase(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/d-dev-c' => '*',
            ],
            'require-dev' => [
                'test/a' => '*',
            ],
        ]);

        $this->assertMergePlan([
            'params' => [
                'test/a' => [
                    'config/params.php',
                ],
            ],
            'web' => [
                'test/d-dev-c' => [
                    'config/web.php',
                ],
                'test/a' => [
                    'config/web.php',
                ],
            ],
        ]);
    }
}
