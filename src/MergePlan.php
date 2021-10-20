<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

/**
 * @internal
 */
final class MergePlan
{
    /**
     * @psalm-var array<string, array<string, array<string, string[]>>>
     */
    private array $mergePlan;

    /**
     * @psalm-param array<string, array<string, array<string, string[]>>> $mergePlan
     */
    public function __construct(array $mergePlan = [])
    {
        $this->mergePlan = $mergePlan;
    }

    /**
     * Adds an item to the merge plan.
     *
     * @param string $file The config file.
     * @param string $package The package name.
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function add(
        string $file,
        string $package,
        string $group,
        string $environment = Options::DEFAULT_ENVIRONMENT
    ): void {
        $this->mergePlan[$environment][$group][$package][] = $file;
    }

    /**
     * Adds a multiple items to the merge plan.
     *
     * @param string[] $files The config files.
     * @param string $package The package name.
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function addMultiple(
        array $files,
        string $package,
        string $group,
        string $environment = Options::DEFAULT_ENVIRONMENT
    ): void {
        $this->mergePlan[$environment][$group][$package] = $files;
    }

    /**
     * Returns the merge plan group.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @return array<string, string[]>
     */
    public function getGroup(string $group, string $environment = Options::DEFAULT_ENVIRONMENT): array
    {
        return $this->mergePlan[$environment][$group] ?? [];
    }

    public function getGroupFiles(string $group, string $environment): array
    {
        if (isset($this->mergePlan[$environment][$group])) {
            $data = $this->mergePlan[$environment][$group];
        } else {
            if (
                $environment === Options::DEFAULT_ENVIRONMENT
                || !isset($this->mergePlan[Options::DEFAULT_ENVIRONMENT][$group])
            ) {
                $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
            }

            $data = $this->mergePlan[Options::DEFAULT_ENVIRONMENT][$group];
        }

        foreach ($data as $package => $files) {
            foreach ($files as $file) {
                if (Options::isVariable($file)) {
                    $variable = $this->prepareVariable($file, $group, $environment);
                    $this->buildGroup($variable);

                    continue;
                }
            }
        }
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

        if (!$this->hasGroup($name, $environment) && !$this->hasGroup($name)) {
            $this->throwException(sprintf('The "%s" configuration group does not exist.', $name));
        }

        return $name;
    }

    /**
     * Returns the merge plan as an array.
     *
     * @psalm-return array<string, array<string, array<string, string[]>>>
     */
    public function toArray(): array
    {
        return $this->mergePlan;
    }

    /**
     * Checks whether the group exists in the merge plan.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @return bool Whether the group exists in the merge plan.
     */
    public function hasGroup(string $group, string $environment = Options::DEFAULT_ENVIRONMENT): bool
    {
        return isset($this->mergePlan[$environment][$group]);
    }

    /**
     * Checks whether the environment exists in the merge plan.
     *
     * @param string $environment The environment name.
     *
     * @return bool Whether the environment exists in the merge plan.
     */
    public function hasEnvironment(string $environment): bool
    {
        return isset($this->mergePlan[$environment]);
    }
}
