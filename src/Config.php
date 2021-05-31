<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function array_key_exists;
use function is_array;
use function is_file;
use function is_int;
use function trim;

/**
 * Config takes merge plan prepared by {@see ComposerEventHandler} and executes actual merge for the config group
 * specified.
 */
final class Config
{
    /**
     * @var string Path to composer.json directory.
     */
    private string $rootPath;
    private string $configsPath;
    private string $environment;
    private string $relativeConfigsPath;

    /**
     * @psalm-var array<string, array<string, array<string, list<string>>>>
     */
    private array $mergePlan;

    /**
     * @psalm-var array<string, array<string, array>>
     */
    private array $build = [];

    /**
     * @param string $rootPath The path to the project root where composer.json is located.
     * @param string|null $configsPath The path to where configs are stored.
     * @param string|null $environment The environment name.
     *
     * @throws ErrorException If the environment does not exist.
     */
    public function __construct(string $rootPath, string $configsPath = null, string $environment = null)
    {
        $this->rootPath = $rootPath;
        $this->relativeConfigsPath = trim($configsPath ?? Options::DEFAULT_CONFIGS_DIRECTORY, '/');
        $this->configsPath = $this->rootPath . '/' . $this->relativeConfigsPath;
        $this->environment = $environment ?? Options::DEFAULT_ENVIRONMENT;

        /** @psalm-suppress UnresolvableInclude, MixedAssignment */
        $this->mergePlan = require $this->configsPath . '/' . Options::MERGE_PLAN_FILENAME;

        if (!isset($this->mergePlan[$this->environment])) {
            $this->throwException(sprintf('The "%s" configuration environment does not exist.', $this->environment));
        }
    }

    /**
     * Builds and returns the configuration of the group.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If the group does not exist or an error occurred during the build.
     *
     * @return array The configuration of the group.
     */
    public function get(string $group): array
    {
        if (isset($this->build[$this->environment][$group])) {
            return $this->build[$this->environment][$group];
        }

        $this->buildGroup('params', $this->environment);
        $environment = $this->prepareEnvironmentGroup($group, $this->environment);

        $this->buildGroup($group, $environment);
        return $this->build[$environment][$group];
    }

    /**
     * Builds the configuration of the group.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @throws ErrorException If an error occurred during the build.
     */
    private function buildGroup(string $group, string $environment): void
    {
        $environment = $this->prepareEnvironmentGroup($group, $environment);

        if (isset($this->build[$environment][$group])) {
            return;
        }

        $this->build[$environment][$group] = [];

        foreach ($this->mergePlan[$environment][$group] as $packageName => $files) {
            foreach ($files as $file) {
                if (Options::isVariable($file)) {
                    $variable = $this->prepareVariable($file, $group, $environment);
                    $this->buildGroup($variable, $environment);

                    $this->build[$environment][$group] = $this->merge(
                        [$file, $group, $environment, $packageName],
                        '',
                        $this->build[$environment][$group],
                        $this->build[$environment][$variable] ?? $this->buildRootGroup($variable, $environment),
                    );
                    continue;
                }

                $isOptional = Options::isOptional($file);
                if ($isOptional) {
                    $file = substr($file, 1);
                }

                $path = $this->getConfigsPath($packageName) . '/' . $file;

                if (Options::containsWildcard($file)) {
                    $matches = glob($path);

                    foreach ($matches as $match) {
                        $this->build[$environment][$group] = $this->merge(
                            [$file, $group, $environment, $packageName],
                            '',
                            $this->build[$environment][$group],
                            $this->buildRootGroup($group, $environment),
                            $this->buildFile($group, $match),
                        );
                    }
                    continue;
                }

                if ($isOptional && !is_file($path)) {
                    continue;
                }

                $this->build[$environment][$group] = $this->merge(
                    [$file, $group, $environment, $packageName],
                    '',
                    $this->build[$environment][$group],
                    $this->buildRootGroup($group, $environment),
                    $this->buildFile($group, $path),
                );
            }
        }
    }

    /**
     * Builds the configuration of the root group if it exists.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @throws ErrorException If an error occurred during the build.
     *
     * @return array The configuration of the root group or the empty array.
     */
    private function buildRootGroup(string $group, string $environment): array
    {
        if ($environment === Options::DEFAULT_ENVIRONMENT || !isset($this->mergePlan[Options::DEFAULT_ENVIRONMENT][$group])) {
            return [];
        }

        $this->buildGroup($group, Options::DEFAULT_ENVIRONMENT);
        return $this->build[Options::DEFAULT_ENVIRONMENT][$group];
    }

    /**
     * Builds the configuration from the file.
     *
     * @param string $group The group name.
     * @param string $filePath The file path.
     *
     * @throws ErrorException If an error occurred during the build.
     *
     * @return array The configuration from the file.
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
            $scope['params'] = $this->build[$this->environment]['params'] ?? $this->build[Options::DEFAULT_ENVIRONMENT]['params'];
        }

        /** @psalm-suppress TooManyArguments */
        return $scopeRequire($this, $filePath, $scope);
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * @param array $context Context containing the name of the file, group, assembly, and package.
     * @param string $path The file path.
     * @param array ...$args Two or more arrays to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     *
     * @psalm-param array{string, string, string, string} $context
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
                        $this->throwException($this->getDuplicateErrorMessage($k, $path, $context));
                    }

                    /** @var mixed */
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a duplicate key error message.
     *
     * @param string $key The duplicate key.
     * @param string $path The file path.
     *
     * @return string The duplicate key error message.
     *
     * @psalm-param array{string, string, string, string} $context
     */
    private function getDuplicateErrorMessage(string $key, string $path, array $context): string
    {
        [$file, $group, $environment, $packageName] = $context;

        $config = $this->mergePlan[$environment][$group];
        unset($config[$packageName]);

        $configPaths = [$this->getRelativeConfigPath($packageName, $file)];
        foreach ($config as $package => $packageConfigs) {
            foreach ($packageConfigs as $packageConfig) {
                if (Options::isVariable($packageConfig)) {
                    continue;
                }

                if (Options::isOptional($packageConfig)) {
                    $packageConfig = substr($packageConfig, 1);
                }

                $fullConfigPath = $this->getConfigsPath($package) . '/' . $packageConfig;

                if (is_file($fullConfigPath)) {
                    $configContents = file_get_contents($fullConfigPath);
                    if (strpos($configContents, $key) !== false) {
                        $configPaths[] = $this->getRelativeConfigPath($package, $packageConfig);
                    }
                }
            }
        }

        $configPaths = array_map(
            static fn (string $path) => ' - ' . $path,
            $configPaths
        );

        usort($configPaths, static function (string $a, string $b) {
            $countDirsA = substr_count($a, '/');
            $countDirsB = substr_count($b, '/');
            return $countDirsA === $countDirsB ? $a <=> $b : $countDirsA <=> $countDirsB;
        });

        return sprintf(
            "Duplicate key \"%s\" in configs:\n%s",
            $path ? $path . ' => ' . $key : $key,
            implode("\n", $configPaths)
        );
    }

    /**
     * Checks the group name and returns actual environment name.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @throws ErrorException If the group does not exist.
     *
     * @return string The actual environment name.
     */
    private function prepareEnvironmentGroup(string $group, string $environment): string
    {
        if (!isset($this->mergePlan[$environment][$group])) {
            if ($environment === Options::DEFAULT_ENVIRONMENT || !isset($this->mergePlan[Options::DEFAULT_ENVIRONMENT][$group])) {
                $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
            }

            return Options::DEFAULT_ENVIRONMENT;
        }

        return $environment;
    }

    /**
     * Checks the configuration variable and returns its name.
     *
     * @param string $variable The variable.
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @throws ErrorException If the variable name is not valid.
     *
     * @return string The variable name.
     */
    private function prepareVariable(string $variable, string $group, string $environment): string
    {
        $name = substr($variable, 1);

        if ($name === $group) {
            $this->throwException(sprintf(
                'The variable "%s" must not be located inside the "%s" config group.',
                "$variable",
                "$name",
            ));
        }

        if (!isset($this->mergePlan[$environment][$name]) && !isset($this->mergePlan[Options::DEFAULT_ENVIRONMENT][$name])) {
            $this->throwException(sprintf('The "%s" configuration group does not exist.', $name));
        }

        return $name;
    }

    /**
     * @param string $message
     *
     * @throws ErrorException
     */
    private function throwException(string $message): void
    {
        throw new ErrorException($message, 0, E_USER_ERROR);
    }

    /**
     * Get path to package configs.
     *
     * @param string $packageName Name of the package. {@see Options::ROOT_PACKAGE_NAME} stands for the root package.
     *
     * @return string Path to package configs.
     */
    private function getConfigsPath(string $packageName): string
    {
        return $packageName === Options::ROOT_PACKAGE_NAME ? $this->rootPath : "$this->configsPath/$packageName";
    }

    /**
     * Get relative path to package config.
     *
     * @param string $packageName Name of the package. {@see Options::ROOT_PACKAGE_NAME} stands for the root package.
     * @param string $file Config file.
     *
     * @return string Relative path to package configs.
     */
    private function getRelativeConfigPath(string $packageName, string $file): string
    {
        return $packageName === Options::ROOT_PACKAGE_NAME ? $file : "$this->relativeConfigsPath/$packageName/$file";
    }
}
