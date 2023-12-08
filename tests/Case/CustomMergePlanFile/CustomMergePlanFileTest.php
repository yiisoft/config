<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Case\CustomMergePlanFile;

use Yiisoft\Config\Tests\Case\BaseTestCase;

final class CustomMergePlanFileTest extends BaseTestCase
{
    public function testBase(): void
    {
        $config = $this->prepareConfig(
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
                'config-plugin-options' => [
                    'merge-plan-file' => 'custom-merge-plan.php',
                ],
            ],
            mergePlanFile: 'custom-merge-plan.php',
        );

        $this->assertFileExists(__DIR__ . '/custom-merge-plan.php');
        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'b-params-key' => 'b-params-value',
                'root-params-key' => 'root-params-value',
            ],
            $config->get('params')
        );
        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'b-web-override-value',
                'root-web-key' => 'root-params-value',
            ],
            $config->get('web')
        );
    }
}
