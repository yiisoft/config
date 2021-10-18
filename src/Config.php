<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function extract;
use function func_get_arg;
use function glob;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function substr;

/**
 * Config takes merge plan prepared by {@see \Yiisoft\Config\Composer\EventHandler}
 * and executes actual merge for the config group specified.
 */
final class Config
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private Merger $merger;
    private string $environment;
    private string $paramsGroup;
    private bool $isBuildingParams = false;

    /**
     * @psalm-var array<string, array<string, array>>
     */
    private array $build = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param string|null $environment The environment name.
     * @param string[] $recursiveMergeGroups Names of config groups that should be merged recursively.
     *
     * @throws ErrorException If the environment does not exist.
     */
    public function __construct(
        ConfigPaths $paths,
        string $environment = null,
        array $recursiveMergeGroups = [],
        string $paramsGroup = 'params'
    ) {
        $this->paths = $paths;
        $this->environment = $environment ?? Options::DEFAULT_ENVIRONMENT;
        $this->paramsGroup = $paramsGroup;

        /** @psalm-suppress UnresolvableInclude, MixedArgument */
        $this->mergePlan = new MergePlan(require $this->paths->absolute(Options::MERGE_PLAN_FILENAME));

        if (!$this->mergePlan->hasEnvironment($this->environment)) {
            $this->throwException(sprintf('The "%s" configuration environment does not exist.', $this->environment));
        }

        $this->merger = new Merger($this->paths, $this->mergePlan, $recursiveMergeGroups);
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

        $this->buildParams();
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

        $this->build[$environment][$group] = $this->buildRootGroup($group, $environment);

        foreach ($this->mergePlan->getGroup($group, $environment) as $package => $files) {
            foreach ($files as $file) {
                if (Options::isVariable($file)) {
                    $variable = $this->prepareVariable($file, $group, $environment);
                    $this->buildGroup($variable, $environment);

                    $this->build[$environment][$group] = $this->merger->merge(
                        new Context($file, $package, $group, $environment),
                        '',
                        $this->build[$environment][$variable] ?? $this->buildRootGroup($variable, $environment),
                        $this->build[$environment][$group],
                    );
                    continue;
                }

                $isOptional = Options::isOptional($file);
                if ($isOptional) {
                    $file = substr($file, 1);
                }

                $filePath = $this->paths->absolute($file, $package);

                if (Options::containsWildcard($file)) {
                    foreach (glob($filePath) as $match) {
                        $this->build[$environment][$group] = $this->merger->merge(
                            new Context($match, $package, $group, $environment),
                            '',
                            $this->build[$environment][$group],
                            $this->buildFile($match),
                        );
                    }
                    continue;
                }

                if ($isOptional && !is_file($filePath)) {
                    continue;
                }

                $this->build[$environment][$group] = $this->merger->merge(
                    new Context($file, $package, $group, $environment),
                    '',
                    $this->build[$environment][$group],
                    $this->buildFile($filePath),
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
        if ($environment === Options::DEFAULT_ENVIRONMENT || !$this->mergePlan->hasGroup($group)) {
            return [];
        }

        $this->buildGroup($group, Options::DEFAULT_ENVIRONMENT);
        return $this->build[Options::DEFAULT_ENVIRONMENT][$group];
    }

    /**
     * Builds the configuration from the file.
     *
     * @param string $filePath The file path.
     *
     * @throws ErrorException If an error occurred during the build.
     *
     * @return array The configuration from the file.
     */
    private function buildFile(string $filePath): array
    {
        $scopeRequire = static function (): array {
            /** @psalm-suppress InvalidArgument, MissingClosureParamType */
            set_error_handler(static function (int $errorNumber, string $errorString, string $errorFile, int $errorLine) {
                throw new ErrorException($errorString, $errorNumber, 0, $errorFile, $errorLine);
            });

            /** @psalm-suppress MixedArgument */
            extract(func_get_arg(1), EXTR_SKIP);
            /**
             * @psalm-suppress UnresolvableInclude
             * @psalm-var array
             */
            $result = require func_get_arg(0);
            restore_error_handler();
            return $result;
        };

        $scope = [];

        if (!$this->isBuildingParams) {
            $scope['config'] = $this;
            $scope['params'] = $this->build[$this->environment][$this->paramsGroup]
                ?? $this->build[Options::DEFAULT_ENVIRONMENT][$this->paramsGroup];
        }

        /** @psalm-suppress TooManyArguments */
        return $scopeRequire($filePath, $scope);
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
        if (!$this->mergePlan->hasGroup($group, $environment)) {
            if ($environment === Options::DEFAULT_ENVIRONMENT || !$this->mergePlan->hasGroup($group)) {
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

        if (!$this->mergePlan->hasGroup($name, $environment) && !$this->mergePlan->hasGroup($name)) {
            $this->throwException(sprintf('The "%s" configuration group does not exist.', $name));
        }

        return $name;
    }

    private function buildParams(): void
    {
        $this->isBuildingParams = true;
        $this->buildGroup($this->paramsGroup, $this->environment);
        $this->isBuildingParams = false;
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
}
