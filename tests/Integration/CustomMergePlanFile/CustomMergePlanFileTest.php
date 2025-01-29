<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\CustomMergePlanFile;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class CustomMergePlanFileTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            ['my-merge-plan.php'],
            ['test/my-merge-plan.php'],
            ['../my-merge-plan.php'],
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(string $mergePlanFIle): void
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
                'config-plugin-options' => [
                    'merge-plan-file' => $mergePlanFIle,
                ],
            ],
            mergePlanFile: $mergePlanFIle,
        );

        $this->assertFileExists(__DIR__ . '/' . $mergePlanFIle);
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
