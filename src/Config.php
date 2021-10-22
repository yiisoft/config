<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function extract;
use function func_get_arg;
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
    private Merger $merger;
    private FilesExtractor $filesExtractor;
    private string $paramsGroup;
    private bool $isBuildingParams = false;

    /**
     * @psalm-var array<string, array>
     */
    private array $build = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param string|null $environment The environment name.
     * @param object[] $modifiers Modifiers that affect merge process.
     * @param string $paramsGroup Group name for $params.
     *
     * @throws ErrorException If the environment does not exist.
     */
    public function __construct(
        ConfigPaths $paths,
        string $environment = null,
        array $modifiers = [],
        string $paramsGroup = 'params'
    ) {
        $environment = $environment ?? Options::DEFAULT_ENVIRONMENT;
        $this->paramsGroup = $paramsGroup;

        /** @psalm-suppress UnresolvableInclude, MixedArgument */
        $mergePlan = new MergePlan(require $paths->absolute(Options::MERGE_PLAN_FILENAME));

        if (!$mergePlan->hasEnvironment($environment)) {
            $this->throwException(sprintf('The "%s" configuration environment does not exist.', $environment));
        }

        $this->merger = new Merger($paths, $modifiers);
        $this->filesExtractor = new FilesExtractor($paths, $mergePlan, $environment);
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

        $this->runBuildParams();
        $this->runBuildGroup($group);

        return $this->build[$group];
    }

    /**
     * @throws ErrorException If an error occurred during the build.
     */
    private function runBuildParams(): void
    {
        $this->isBuildingParams = true;
        $this->runBuildGroup($this->paramsGroup);
        $this->isBuildingParams = false;
    }

    /**
     * @throws ErrorException If an error occurred during the build.
     */
    private function runBuildGroup(string $group): void
    {
        $this->merger->reset();
        $this->buildGroup($group);
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

        foreach ($this->filesExtractor->extract($group) as $file => $context) {
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
