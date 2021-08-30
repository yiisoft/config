<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testGet(): void
    {
        $config = $this->createConfig();

        $this->assertSame($config->get('common'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
        ]);

        $this->assertSame($config->get('params'), [
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
        ]);

        $this->assertSame($config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);
    }

    public function testGetWithEnvironment(): void
    {
        $config = $this->createConfig('alfa');

        $this->assertSame($config->get('common'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
        ]);

        $this->assertSame($config->get('main'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
            'alfa-main-key' => 'alfa-main-value',
        ]);

        $this->assertSame($config->get('params'), [
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'alfa-params-key' => 'alfa-params-value',
        ]);

        $this->assertSame($config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
            'alfa-web-key' => 'alfa-web-value',
            'alfa-web2-key' => 'alfa-web2-value',
        ]);
    }

    public function testGetWithScopeExistenceCheck(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame($config->get('params'), [
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
            'beta-params-key' => 'beta-params-value',
            'beta-params-isset-config' => false,
            'beta-params-isset-params' => false,
        ]);

        $this->assertSame($config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
            'beta-web-key' => 'beta-web-value',
            'beta-web-isset-config' => true,
            'beta-web-isset-params' => true,
        ]);
    }

    public function testGetWithEnvironmentVariableExistAndRootVariableNotExist(): void
    {
        $config = $this->createConfig('beta');

        $this->assertSame($config->get('events'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'root-common-nested-key-1' => 'root-common-nested-value-1',
            'root-common-nested-key-2' => 'root-common-nested-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
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
        $configsDir = dirname(__DIR__) . '/configs/duplicate-keys';
        $config = new Config($configsDir, '/config/packages');

        $this->expectException(ErrorException::class);
        $this->expectErrorMessage(
            'Duplicate key "age" in configs:' . "\n" .
            ' - config/params.php' . "\n" .
            ' - config/packages/test/a/params.php' . "\n" .
            ' - config/packages/test/b/params.php'
        );
        $config->get('params');
    }

    public function testDuplicateKeysWithPathErrorMessage(): void
    {
        $configsDir = dirname(__DIR__) . '/configs/duplicate-keys-with-params';
        $config = new Config($configsDir, '/packages', null, [
           'params',
        ]);

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
        $config->get('params');
    }

    private function createConfig(string $environment = null): Config
    {
        return new Config(dirname(__DIR__) . '/configs/dummy', 'config/packages', $environment);
    }
}
