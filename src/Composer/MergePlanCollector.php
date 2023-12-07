<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Yiisoft\Config\MergePlan;
use Yiisoft\Config\Options;

/**
 * @internal
 *
 * @psalm-import-type MergePlanType from MergePlan
 */
final class MergePlanCollector
{
    /**
     * @psalm-var MergePlanType
     */
    private array $mergePlan = [];

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
     * Adds an empty environment item to the merge plan.
     *
     * @param string $environment The environment name.
     */
    public function addEnvironmentWithoutConfigs(string $environment): void
    {
        $this->mergePlan[$environment] = [];
    }

    /**
     * Add empty group if it not exists.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function addGroup(string $group, string $environment = Options::DEFAULT_ENVIRONMENT): void
    {
        if (!isset($this->mergePlan[$environment][$group])) {
            $this->mergePlan[$environment][$group] = [];
        }
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

    /**
     * Returns the merge plan as an array.
     *
     * @psalm-return MergePlanType
     */
    public function generate(): array
    {
        return $this->mergePlan;
    }
}
