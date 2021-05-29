<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Config\Config;

use function dirname;

final class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(dirname(__DIR__) . '/configs/dummy', 'config/packages');
        parent::setUp();
    }

    public function testGet(): void
    {
        $this->assertSame($this->config->get('common'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
        ]);

        $this->assertSame($this->config->get('params'), [
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
        ]);

        $this->assertSame($this->config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);
    }

    public function testGetWithEnvironment(): void
    {
        $this->assertSame($this->config->get('common', 'environment'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
        ]);

        $this->assertSame($this->config->get('main', 'environment'), [
            'environment-web-key' => 'environment-web-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
            'environment-main-key' => 'environment-main-value',
        ]);

        $this->assertSame($this->config->get('params', 'environment'), [
            'environment-params-key' => 'environment-params-value',
            'root-params-key' => 'root-params-value',
            'root-params-local-key' => 'root-params-local-value',
            'a-params-key' => 'a-params-value',
            'b-params-key' => 'b-params-value',
        ]);

        $this->assertSame($this->config->get('web', 'environment'), [
            'environment-web-key' => 'environment-web-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);
    }

    public function testGetWithGettingGroupAgain(): void
    {
        $this->assertSame($this->config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);

        $this->assertSame($this->config->get('web'), [
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);

        $this->assertSame($this->config->get('web', 'environment'), [
            'environment-web-key' => 'environment-web-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);

        $this->assertSame($this->config->get('web', 'environment'), [
            'environment-web-key' => 'environment-web-value',
            'root-common-key-1' => 'root-common-value-1',
            'root-common-key-2' => 'root-common-value-2',
            'a-common-key' => 'a-common-value',
            'b-common-key' => 'b-common-value',
            'root-web-key' => 'root-web-value',
            'a-web-key' => 'a-web-value',
            'b-web-key' => 'b-web-value',
        ]);
    }

    public function testGetThrowExceptionForBuildNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration build does not exist.');
        $this->config->get('web', 'not-exist');
    }

    public function testGetThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->config->get('not-exist');
    }

    public function testGetEnvironmentThrowExceptionForGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "not-exist" configuration group does not exist.');
        $this->config->get('not-exist', 'environment');
    }

    public function testGetThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->config->get('failVariableGroupEqual');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupEqual(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The variable "$failVariableGroupEqual" must not be located inside the "failVariableGroupEqual" config group.');
        $this->config->get('failVariableGroupEqual', 'environment');
    }

    public function testGetThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->config->get('failVariableNotExist');
    }

    public function testGetEnvironmentThrowExceptionForVariableGroupNotExist(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectErrorMessage('The "failVariableNotExist" configuration group does not exist.');
        $this->config->get('failVariableNotExist', 'environment');
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
        $config = new Config($configsDir, '/packages');

        $this->expectException(ErrorException::class);
        $this->expectErrorMessageMatches('~^Duplicate key "name => first-name" in~');
        $config->get('params');
    }
}
