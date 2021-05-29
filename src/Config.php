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
    private string $relativeConfigsPath;
    private string $currentBuildName = Options::DEFAULT_BUILD;

    /**
     * @psalm-var array<string, array<string, array<string, list<string>>>>
     */
    private array $mergePlan;

    /**
     * @psalm-var array<string, array<string, array>>
     */
    private array $build = [];

    /**
     * @param string $rootPath Path to the project root where composer.json is located.
     * @param string|null $configsPath Path to where configs are stored.
     */
    public function __construct(string $rootPath, string $configsPath = null)
    {
        $this->rootPath = $rootPath;
        $this->relativeConfigsPath = trim($configsPath ?? Options::DEFAULT_CONFIGS_DIRECTORY, '/');
        $this->configsPath = $this->rootPath . '/' . $this->relativeConfigsPath;

        /** @psalm-suppress UnresolvableInclude, MixedAssignment */
        $this->mergePlan = require $this->configsPath . '/' . Options::MERGE_PLAN_FILENAME;
    }

    /**
     * Builds and returns the configuration of the build group.
     *
     * @param string $group The group name.
     * @param string $build The build name.
     *
     * @throws ErrorException If the build or group does not exist or or an error occurred during the build.
     *
     * @return array The configuration of the build group.
     */
    public function get(string $group, string $build = Options::DEFAULT_BUILD): array
    {
        if (isset($this->build[$build][$group])) {
            return $this->build[$build][$group];
        }

        $this->currentBuildName = $build;
        $this->buildGroup('params', Options::DEFAULT_BUILD);

        if ($build !== Options::DEFAULT_BUILD && isset($this->mergePlan[$build]['params'])) {
            $this->buildGroup('params', $build, $this->build[Options::DEFAULT_BUILD]['params']);
        }

        $build = $this->checkBuildGroup($group, $build);
        $rootBuildGroupConfig = [];

        if ($build !== Options::DEFAULT_BUILD && isset($this->mergePlan[Options::DEFAULT_BUILD][$group])) {
            $this->buildGroup($group, Options::DEFAULT_BUILD);
            $rootBuildGroupConfig = $this->build[Options::DEFAULT_BUILD][$group];
        }

        $this->buildGroup($group, $build, $rootBuildGroupConfig);
        return $this->build[$build][$group];
    }

    /**
     * Builds the configuration of the build group.
     *
     * @param string $group The group name.
     * @param string $build The build name.
     * @param array $rootBuildGroupConfig The configuration of the root group of the build,
     * when building a non-root {@see Options::DEFAULT_BUILD} build.
     *
     * @throws ErrorException If an error occurred during the build.
     */
    private function buildGroup(string $group, string $build, array $rootBuildGroupConfig = []): void
    {
        if (isset($this->build[$build][$group])) {
            return;
        }

        $build = $this->checkBuildGroup($group, $build);
        $this->build[$build][$group] = [];

        foreach ($this->mergePlan[$build][$group] as $packageName => $files) {
            foreach ($files as $file) {
                if (Options::isVariable($file)) {
                    $variable = $this->checkVariable($file, $group, $build);

                    if ($build !== Options::DEFAULT_BUILD && isset($this->mergePlan[Options::DEFAULT_BUILD][$variable])) {
                        $this->buildGroup($variable, Options::DEFAULT_BUILD);
                        $rootVariableBuildConfig = $this->build[Options::DEFAULT_BUILD][$variable];
                    }

                    $this->buildGroup($variable, $build, $rootVariableBuildConfig ?? []);
                    $this->build[$build][$group] = $this->merge(
                        [$file, $group, $build, $packageName],
                        '',
                        $this->build[$build][$group],
                        $this->build[$build][$variable] ?? $this->build[Options::DEFAULT_BUILD][$variable],
                        $rootBuildGroupConfig,
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
                        $this->build[$build][$group] = $this->merge(
                            [$file, $group, $build, $packageName],
                            '',
                            $this->build[$build][$group],
                            $this->buildFile($group, $match),
                            $rootBuildGroupConfig,
                        );
                    }
                    continue;
                }

                if ($isOptional && !is_file($path)) {
                    continue;
                }

                $this->build[$build][$group] = $this->merge(
                    [$file, $group, $build, $packageName],
                    '',
                    $this->build[$build][$group],
                    $this->buildFile($group, $path),
                    $rootBuildGroupConfig,
                );
            }
        }
    }

    /**
     * Builds the configuration from the file.
     *
     * @param string $group The group name.
     * @param string $filePath The file path.
     *
     * @return array The configuration from the file.
     *
     * @throws ErrorException If an error occurred during the build.
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
            $scope['params'] = (
                $this->build[$this->currentBuildName]['params'] ?? $this->build[Options::DEFAULT_BUILD]['params']
            );
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
        [$file, $group, $build, $packageName] = $context;

        $config = $this->mergePlan[$build][$group];
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
     * Checks the build group name and returns actual build name.
     *
     * @param string $group The group name.
     * @param string $build The build name.
     *
     * @throws ErrorException If the build or group does not exist.
     *
     * @return string The actual build name.
     */
    private function checkBuildGroup(string $group, string $build): string
    {
        if (!isset($this->mergePlan[$build])) {
            $this->throwException(sprintf('The "%s" configuration build does not exist.', $build));
        }

        if (!isset($this->mergePlan[$build][$group])) {
            if ($build === Options::DEFAULT_BUILD || !isset($this->mergePlan[Options::DEFAULT_BUILD][$group])) {
                $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
            }

            return Options::DEFAULT_BUILD;
        }

        return $build;
    }

    /**
     * Checks the configuration variable and returns its name
     *
     * @param string $variable The variable.
     * @param string $group The group name.
     * @param string $build The build name.
     *
     * @throws ErrorException If the variable name is not valid.
     *
     * @return string The variable name.
     */
    private function checkVariable(string $variable, string $group, string $build): string
    {
        $name = substr($variable, 1);

        if ($name === $group) {
            $this->throwException(sprintf(
                'The variable "%s" must not be located inside the "%s" config group.',
                "$variable",
                "$name",
            ));
        }

        if (!isset($this->mergePlan[$build][$name]) && !isset($this->mergePlan[Options::DEFAULT_BUILD][$name])) {
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
     * @param string $packageName Name of the package. {@see Options::DEFAULT_BUILD} stands for the root package.
     *
     * @return string Path to package configs.
     */
    private function getConfigsPath(string $packageName): string
    {
        return $packageName === Options::DEFAULT_BUILD ? $this->rootPath : "$this->configsPath/$packageName";
    }

    /**
     * Get relative path to package config.
     *
     * @param string $packageName Name of the package. {@see Options::DEFAULT_BUILD} stands for the root package.
     * @param string $file Config file.
     *
     * @return string Relative path to package configs.
     */
    private function getRelativeConfigPath(string $packageName, string $file): string
    {
        return $packageName === Options::DEFAULT_BUILD ? $file : "$this->relativeConfigsPath/$packageName/$file";
    }
}
