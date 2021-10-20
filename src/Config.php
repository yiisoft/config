<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function extract;
use function func_get_arg;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

/**
 * Config takes merge plan prepared by {@see \Yiisoft\Config\Composer\EventHandler}
 * and executes actual merge for the config group specified.
 */
final class Config
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private Merger $merger;
    private FilesExctractor $filesExctractor;
    private string $environment;
    private string $paramsGroup;
    private bool $isBuildingParams = false;

    /**
     * @psalm-var array<string, array>
     */
    private array $build = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param string|null $environment The environment name.
     * @param object[] $modifiers
     * @param string $paramsGroup
     *
     * @throws ErrorException If the environment does not exist.
     */
    public function __construct(
        ConfigPaths $paths,
        string $environment = null,
        array $modifiers = [],
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

        $this->merger = new Merger($this->paths, $this->mergePlan, $modifiers);
        $this->filesExctractor = new FilesExctractor($this->paths, $this->mergePlan, $this->environment);
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
        if (isset($this->build[$group])) {
            return $this->build[$group];
        }

        $this->merger->prepare();
        $this->buildParams();
        $this->merger->prepare();
        $this->buildGroup($group);

        return $this->build[$group];
    }

    /**
     * Builds the configuration of the group.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If an error occurred during the build.
     */
    private function buildGroup(string $group): void
    {
        if (isset($this->build[$group])) {
            return;
        }

        $this->build[$group] = [];

        foreach ($this->filesExctractor->extract($group) as $file => $context) {
            if (Options::isVariable($file)) {
                $variable = $this->prepareVariable($file, $group);
                $array = $this->get($variable);
            } else {
                $array = $this->buildFile($file);
            }
            $this->build[$group] = $this->merger->merge(
                $context,
                [],
                $this->build[$group],
                $array,
            );
        }
    }

    /**
     * Checks the configuration variable and returns its name.
     *
     * @param string $variable The variable.
     * @param string $group The group name.
     *
     * @throws ErrorException If the variable name is not valid.
     *
     * @return string The variable name.
     */
    private function prepareVariable(string $variable, string $group): string
    {
        $name = substr($variable, 1);

        if ($name === $group) {
            $this->throwException(sprintf(
                'The variable "%s" must not be located inside the "%s" config group.',
                "$variable",
                "$name",
            ));
        }

        return $name;
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
            $scope['params'] = $this->build[$this->paramsGroup];
        }

        /** @psalm-suppress TooManyArguments */
        return $scopeRequire($filePath, $scope);
    }

    private function buildParams(): void
    {
        $this->isBuildingParams = true;
        $this->buildGroup($this->paramsGroup);
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
