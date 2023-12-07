<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 *
 * @psalm-type MergePlanType = array<string, array<string, array<string, string[]>>>
 */
final class MergePlan
{
    /**
     * @psalm-var MergePlanType
     */
    private array $mergePlan;

    public function __construct(string $file)
    {
        /** @psalm-suppress UnresolvableInclude */
        $this->mergePlan = require $file;
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
     * Checks whether the group exists in the merge plan.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @return bool Whether the group exists in the merge plan.
     */
    public function hasGroup(string $group, string $environment): bool
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
