<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

final class ConfigTest extends TestCase
{
    public function testGet(): void
    {
        $config = $this->createConfig();

        $this->assertSame($config->get('common'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ]);

        $this->assertSame($config->get('params'), [
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
        ]);

        $this->assertSame($config->get('web'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'b-web-override-value',
            'root-web-key' => 'root-web-value',
        ]);
    }

    public function testGetWithEnvironment(): void
    {
        $config = $this->createConfig('alfa');

        $this->assertSame($config->get('common'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
        ]);

        $this->assertSame($config->get('main'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'alfa-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'alfa-web-override-value',
            'root-web-key' => 'root-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
            'alfa-main-key' => 'alfa-main-value',
        ]);

        $this->assertSame($config->get('params'), [
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'alfa-params-key' => 'alfa-params-value',
        ]);

        $this->assertSame($config->get('web'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'a-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'b-web-override-value',
            'root-web-key' => 'root-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
        ]);
    }

    public function testGetWithScopeExistenceCheck(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame($config->get('params'), [
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'beta-params-key' => 'beta-params-value',
            'beta-params-isset-config' => false,
            'beta-params-isset-params' => false,
        ]);

        $this->assertSame($config->get('web'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-web-key' => 'a-web-value',
            'a-web-environment-override-key' => 'beta-web-override-value',
            'b-web-key' => 'b-web-value',
            'b-web-environment-override-key' => 'beta-web-override-value',
            'root-web-key' => 'root-web-value',
            'beta-web-key' => 'beta-web-value',
            'beta-web-isset-config' => true,
            'beta-web-isset-params' => true,
        ]);
    }

    public function testGetWithEnvironmentVariableExistAndRootVariableNotExist(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame($config->get('events'), [
            'a-common-key' => 'a-common-value',
            'a-common-root-override-key' => 'common-root-override-value',
            'b-common-key' => 'b-common-value',
            'b-common-root-override-key' => 'common-root-override-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'root-events-key' => 'root-events-value',
            'beta-events-key' => 'beta-events-value',
        ]);
    }

    public function testGetThrowExceptionForEnvironmentNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration environment does not exist.');
        $this->createConfig('not-exist')->get('web');
    }

    public function testGetThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->createConfig()->get('not-exist');
    }

    public function testGetEnvironmentThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->createConfig('alfa')->get('not-exist');
    }

    public function testGetThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->createConfig()->get('failVariableGroupEqual');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->createConfig('alfa')->get('failVariableGroupEqual');
    }

    public function testGetThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->createConfig()->get('failVariableNotExist');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->createConfig('alfa')->get('failVariableNotExist');
    }

    public function testDuplicateKeysErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-keys'));

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/params.php' . "\n" .
            ' - vendor/package/a/params.php' . "\n" .
            ' - vendor/package/b/params.php'
        );

        $config->get('params');
    }

    public function testDuplicateKeysWithRecursiveKeyPathErrorMessage(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/duplicate-keys-with-params'), null, [
           'params',
        ]);

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
        $config->get('params');
    }

    private function createConfig(string $environment = null): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/TestAsset/configs/dummy'), $environment);
    }
}
