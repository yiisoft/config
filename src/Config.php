<?php


declare(strict_types=1);


namespace Yiisoft\Config;


use ErrorException;
use function array_key_exists;
use function is_array;
use function is_int;

/**
 * Config takes merge plan prepared by {@see ComposerEventHandler} and executes actual merge for the config group
 * specified.
 */
final class Config
{
    private const MERGE_PLAN_FILENAME = 'merge_plan.php';
    private const DEFAULT_CONFIGS_PATH = '/config/packages';

    /**
     * @var string Path to composer.json directory.
     */
    private string $rootPath;
    private string $configsPath;

    /**
     * @psalm-var array<string, array<string, list<string>>>
     */
    private array $mergePlan;

    /**
     * @psalm-var array<string, array<array-key, mixed>>
     */
    private array $build = [];

    /**
     * @param string $rootPath Path to the project root where composer.json is located.
     * @param string|null $configsPath Path to where configs are stored.
     */
    public function __construct(
        string $rootPath,
        string $configsPath = null
    )
    {
        $this->rootPath = $rootPath;
        $this->configsPath = $this->rootPath . ($configsPath ?? self::DEFAULT_CONFIGS_PATH);

        /** @psalm-suppress UnresolvableInclude, MixedAssignment */
        $this->mergePlan = require $this->configsPath . '/' . self::MERGE_PLAN_FILENAME;
    }

    private function buildGroup(string $group): void
    {
        if (array_key_exists($group, $this->build)) {
            return;
        }

        $this->build[$group] = [];

        foreach ($this->mergePlan[$group] as $packageName => $files) {
            foreach ($files as $file) {
                if ($this->isVariable($file)) {
                    $variableName = substr($file, 1);
                    $this->buildGroup($variableName);
                    $this->build[$group] = $this->merge([$file, $group, $packageName], '', $this->build[$group], $this->build[$variableName]);
                    continue;
                }

                $isOptional = $this->isOptional($file);
                if ($isOptional) {
                    $file = substr($file, 1);
                }

                $path = $this->getConfigsPath($packageName) . '/' . $file;

                if ($this->containsWildcard($file)) {
                    $matches = glob($path);

                    foreach ($matches as $match) {
                        $buildConfig = $this->buildFile($group, $match);
                        $this->build[$group] = $this->merge([$file, $group, $packageName], '', $this->build[$group], $buildConfig);
                    }
                    continue;
                }

                if ($isOptional && !file_exists($path)) {
                    continue;
                }

                $vendorConfigPath = $this->rootPath . '/vendor/' . $packageName . '/' . $file;

                if (!file_exists($path) && file_exists($vendorConfigPath)) {
                    $path = $vendorConfigPath;
                }

                $buildConfig = $this->buildFile($group, $path);
                $this->build[$group] = $this->merge([$file, $group, $packageName], '', $this->build[$group], $buildConfig);
            }
        }
    }

    /**
     * @psalm-param array{string, string, string} $context $context
     */
    private function buildFile(string $group, string $filePath): array
    {
        $scopeRequire = static function (Config $config): array {
            /** @psalm-suppress InvalidArgument, MissingClosureParamType */
            set_error_handler(static function (int $errorNumber, string $errorString, string $errorFile, int $errorLine) {
                throw new ErrorException($errorString, $errorNumber, 0, $errorFile, $errorLine);
            });

            /** @psalm-suppress MixedArgument */
            extract(func_get_arg(2), EXTR_SKIP);
            /**
             * @psalm-suppress UnresolvableInclude
             * @psalm-var array
             */
            $result = require func_get_arg(1);
            restore_error_handler();
            return $result;
        };

        $scope = [];
        if ($group !== 'params') {
            $scope['params'] = $this->build['params'];
        }

        /** @psalm-suppress TooManyArguments */
        return $scopeRequire($this, $filePath, $scope);
    }

    public function get(string $name): array
    {
        $this->buildGroup('params');
        $this->buildGroup($name);
        return $this->build[$name];
    }

    private function containsWildcard(string $file): bool
    {
        return strpos($file, '*') !== false;
    }

    private function isOptional(string $file): bool
    {
        return strpos($file, '?') === 0;
    }

    private function isVariable(string $file): bool
    {
        return strpos($file, '$') === 0;
    }

    /**
     * @psalm-param array{string, string, string} $context
     */
    private function merge(array $context, string $path = '', array ...$args): array
    {
        $result = array_shift($args) ?: [];
        while (!empty($args)) {
            /** @psalm-var mixed $v */
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $result) && $result[$k] !== $v) {
                        /** @var mixed */
                        $result[] = $v;
                    } else {
                        /** @var mixed */
                        $result[$k] = $v;
                    }
                } elseif (is_array($v) && isset($result[$k]) && is_array($result[$k])) {
                    $result[$k] = $this->merge($context, $path ? $path . ' => ' . $k : $k, $result[$k], $v);
                } else {
                    if (array_key_exists($k, $result)) {
                        throw new ErrorException($this->getErrorMessage($k, $path, $context), 0, E_USER_ERROR);
                    }

                    /** @var mixed */
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    /**
     * @psalm-param array{string, string, string} $context
     */
    private function getErrorMessage(string $key, string $path, array $context): string
    {
        [$file, $group, $packageName] = $context;

        $config = $this->mergePlan[$group];
        unset($config[$packageName]);

        $suggestedConfigPaths = [];
        foreach ($config as $package => $packageConfigs) {
            foreach ($packageConfigs as $packageConfig) {
                if ($this->isVariable($packageConfig)) {
                    continue;
                }

                if ($this->isOptional($packageConfig)) {
                    $packageConfig = substr($packageConfig, 1);
                }

                $fullConfigPath = $this->getConfigsPath($package) . '/' . $packageConfig;

                if (file_exists($fullConfigPath)) {
                    $configContents = file_get_contents($fullConfigPath);
                    if (strpos($configContents, $key) !== false) {
                        $suggestedConfigPaths[] = $fullConfigPath;
                    }
                }
            }
        }

        return sprintf(
            'Duplicate key "%s" in "%s". Configs with the same key: "%s".',
            $path ? $path . ' => ' . $key : $key,
            $this->getConfigsPath($packageName) . '/' . $file,
            implode('", "', $suggestedConfigPaths)
        );
    }

    /**
     * Get path to package configs.
     *
     * @param string $packageName Name of the package. "/" stands for application package.
     * @return string Path to package configs.
     */
    private function getConfigsPath(string $packageName): string
    {
        if ($packageName === '/') {
            return $this->rootPath;
        }

        return "$this->configsPath/$packageName";
    }
}
