<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 *
 * @psalm-type FileType = string|list{string,string}
 * @psalm-type MergePlanType = array{
 *     groups: array<string, array<string, list<FileType>>>,
 *     environments: list<string>,
 * }
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
     *
     * @return array<string, list<FileType>>
     */
    public function getGroup(string $group): array
    {
        return $this->mergePlan['groups'][$group] ?? [];
    }

    /**
     * Checks whether the group exists in the merge plan.
     *
     * @param string $group The group name.
     *
     * @return bool Whether the group exists in the merge plan.
     */
    public function hasGroup(string $group): bool
    {
        return isset($this->mergePlan['groups'][$group]);
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
        return in_array($environment, $this->mergePlan['environments']);
    }
}
