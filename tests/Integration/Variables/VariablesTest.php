<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\Variables;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class VariablesTest extends IntegrationTestCase
{
    public function testReverseWithTwoVariables(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                    'web1' => 'web1.php',
                    'web2' => 'web2.php',
                    'web' => [
                        '$web1',
                        '$web2',
                    ],
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                ReverseMerge::groups('web'),
            ],
        );

        $this->assertSame(
            [
                'b' => 2,
                'a' => 1,
            ],
            $config->get('web')
        );
    }

    public static function dataDefaultEnvironment(): array
    {
        return [
            'null' => [null],
            'empty-string' => [''],
            'default-environment' => ['/'],
        ];
    }

    #[DataProvider('dataDefaultEnvironment')]
    public function testDefaultEnvironment(?string $environment): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                    'web1' => 'web1.php',
                    'web2' => 'web2.php',
                    'web' => [
                        '$web1',
                        '$web2',
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: $environment,
        );

        $this->assertSame(
            [
                'a' => 1,
                'b' => 2,
            ],
            $config->get('web')
        );
    }
}
