<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

use function is_array;

/**
 * @see RemoveFromVendor::groups()
 */
final class RemoveGroupsFromVendor
{
    /**
     * @psalm-var array<string,string[]>
     */
    private array $groups;

    /**
     * @psalm-param array<string,string|string[]> $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = [];
        foreach ($groups as $package => $groupNames) {
            $this->groups[$package] = is_array($groupNames) ? $groupNames : [$groupNames];
        }
    }

    /**
     * @return array<string,string[]>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
