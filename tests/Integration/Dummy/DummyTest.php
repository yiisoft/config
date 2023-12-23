<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\Dummy;

use ErrorException;
use Yiisoft\Config\Config;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class DummyTest extends IntegrationTestCase
{
    public function testHas(): void
    {
        $config = $this->prepareConfig();

        $this->assertTrue($config->has('web'));
        $this->assertTrue($config->has('empty'));
        $this->assertFalse($config->has('not-exist'));
    }

    public function testHasWithEnvironment(): void
    {
        $config = $this->prepareConfig('alfa');

        $this->assertTrue($config->has('web'));
        $this->assertTrue($config->has('empty'));
        $this->assertTrue($config->has('common'));
        $this->assertFalse($config->has('not-exist'));
    }

    public function testGet(): void
    {
        $config = $this->prepareConfig();

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame(
            [
                'a-common-key' => 'a-common-value',
                'a-common-root-override-key' => 'common-root-override-value',
                'b-common-key' => 'b-common-value',
                'b-common-root-override-key' => 'common-root-override-value',
                'c-common-key' => 'c-common-value',
                'c-common-root-override-key' => 'common-root-override-value',
                'root-common-key-1' => 'root-common-value-1',
                'root-common-key-2' => 'root-common-value-2',
                'root-common-nested-key-1' => 'root-common-nested-value-1',
                'root-common-nested-key-2' => 'root-common-nested-value-2',
            ],
            $config->get('common')
        );

        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'b-params-key' => 'b-params-value',
                'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'c-params-key' => 'c-params-value',
                'root-params-key' => 'root-params-value',
                'root-params-local-key' => 'root-params-local-value',
            ],
            $config->get('params')
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'c-web-override-value',
                'c-web-key' => 'c-web-value',
                'c-web-environment-override-key' => 'c-web-override-value',
                'root-web-key' => 'root-web-value',
            ],
            $config->get('web')
        );
    }

    public function testGetWithEnvironment(): void
    {
        $config = $this->prepareConfig('alfa');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame(
            [
                'a-common-key' => 'a-common-value',
                'a-common-root-override-key' => 'common-root-override-value',
                'b-common-key' => 'b-common-value',
                'b-common-root-override-key' => 'common-root-override-value',
                'c-common-key' => 'c-common-value',
                'c-common-root-override-key' => 'common-root-override-value',
                'root-common-key-1' => 'root-common-value-1',
                'root-common-key-2' => 'root-common-value-2',
                'root-common-nested-key-1' => 'root-common-nested-value-1',
                'root-common-nested-key-2' => 'root-common-nested-value-2',
            ],
            $config->get('common')
        );

        $this->assertSame(
            [
                'alfa-main-key' => 'alfa-main-value',
                'a-web-environment-override-key' => 'alfa-web-override-value',
                'b-web-environment-override-key' => 'alfa-web-override-value',
                'c-web-environment-override-key' => 'alfa-web-override-value',
            ],
            $config->get('main')
        );

        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'b-params-key' => 'b-params-value',
                'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'c-params-key' => 'c-params-value',
                'root-params-key' => 'root-params-value',
                'root-params-local-key' => 'root-params-local-value',
                'alfa-params-key' => 'alfa-params-value',
            ],
            $config->get('params')
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'b-web-override-value',
                'c-web-key' => 'c-web-value',
                'b-web-environment-override-key' => 'c-web-override-value',
                'c-web-environment-override-key' => 'c-web-override-value',
                'root-web-key' => 'root-web-value',
                'alfa-web-key' => 'alfa-web-value',
                'alfa-web2-key' => 'alfa-web2-value',
            ],
            $config->get('web')
        );
    }

    public function testGetWithScopeExistenceCheck(): void
    {
        $config = $this->prepareConfig('beta');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame(
            [
                'a-params-key' => 'a-params-value',
                'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'b-params-key' => 'b-params-value',
                'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'c-params-key' => 'c-params-value',
                'root-params-key' => 'root-params-value',
                'root-params-local-key' => 'root-params-local-value',
                'beta-params-key' => 'beta-params-value',
                'beta-params-isset-config' => false,
                'beta-params-isset-params' => false,
            ],
            $config->get('params')
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'beta-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'beta-web-override-value',
                'c-web-key' => 'c-web-value',
                'c-web-environment-override-key' => 'beta-web-override-value',
                'root-web-key' => 'root-web-value',
                'beta-web-key' => 'beta-web-value',
                'beta-web-isset-config' => true,
                'beta-web-isset-params' => true,
            ],
            $config->get('web')
        );
    }

    public function testGetWithEnvironmentVariableExistAndRootVariableNotExist(): void
    {
        $config = $this->prepareConfig('beta');

        $this->assertSame([], $config->get('empty'));
        $this->assertSame([], $config->get('emptyVariable'));

        $this->assertSame(
            [
                'root-events-key' => 'root-events-value',
                'beta-events-key' => 'beta-events-value',
            ],
            $config->get('events')
        );
    }

    public function testConfigWithReverseMerge(): void
    {
        $config = $this->prepareConfig(
            modifiers: [
                ReverseMerge::groups('common', 'params'),
            ]
        );

        $this->assertSame(
            [
                'root-common-nested-key-2' => 'root-common-nested-value-2',
                'root-common-nested-key-1' => 'root-common-nested-value-1',
                'a-common-root-override-key' => 'common-root-override-value',
                'b-common-root-override-key' => 'common-root-override-value',
                'c-common-root-override-key' => 'common-root-override-value',
                'root-common-key-2' => 'root-common-value-2',
                'root-common-key-1' => 'root-common-value-1',
                'c-common-key' => 'c-common-value',
                'b-common-key' => 'b-common-value',
                'a-common-key' => 'a-common-value',
            ],
            $config->get('common')
        );

        $this->assertSame(
            [
                'root-params-local-key' => 'root-params-local-value',
                'root-params-key' => 'root-params-value',
                'c-params-key' => 'c-params-value',
                'a-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'b-params-over-vendor-override-key' => 'c-params-over-vendor-override-value',
                'b-params-key' => 'b-params-value',
                'a-params-key' => 'a-params-value',
            ],
            $config->get('params')
        );

        $this->assertSame(
            [
                'a-web-key' => 'a-web-value',
                'a-web-environment-override-key' => 'a-web-override-value',
                'b-web-key' => 'b-web-value',
                'b-web-environment-override-key' => 'c-web-override-value',
                'c-web-key' => 'c-web-value',
                'c-web-environment-override-key' => 'c-web-override-value',
                'root-web-key' => 'root-web-value',
            ],
            $config->get('web')
        );
    }

    public function testGetThrowExceptionForEnvironmentNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration environment does not exist.');
        $this->prepareConfig('not-exist');
    }

    public function testGetThrowExceptionForGroupNotExist(): void
    {
        $config = $this->prepareConfig();

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration group does not exist.');
        $config->get('not-exist');
    }

    public function testGetEnvironmentThrowExceptionForGroupNotExist(): void
    {
        $config = $this->prepareConfig('alfa');

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The "not-exist" configuration group does not exist.');
        $config->get('not-exist');
    }

    private function prepareConfig(
        ?string $environment = null,
        array $modifiers = [],
        bool $echoOutputOnException = false,
    ): Config {
        return $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
                'test/b' => __DIR__ . '/packages/b',
                'test/c' => __DIR__ . '/packages/c',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                    'vendor-override-layer' => 'test/c',
                ],
                'config-plugin' => [
                    'empty' => [],
                    'emptyVariable' => '$empty',
                    'events' => [
                        'events.php',
                        ['beta', 'beta/events.php'],
                    ],
                    'common' => [
                        'common/*.php',
                        'common/*/*.php',
                    ],
                    'params' => [
                        'params.php',
                        '?params-local.php',
                        ['alfa', 'alfa/params.php'],
                        ['beta', 'beta/params.php'],
                    ],
                    'web' => [
                        'web.php',
                        ['alfa', 'alfa/web.php'],
                        ['alfa', 'alfa/web2.php'],
                        ['beta', 'beta/web.php'],
                    ],
                    'main' => [
                        ['alfa', 'alfa/main.php'],
                    ],
                ],
            ],
            configDirectory: 'config',
            environment: $environment,
            modifiers: $modifiers,
            echoOutputOnException: $echoOutputOnException,
        );
    }
}
