<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use ErrorException;
use Yiisoft\Config\MergePlan;
use Yiisoft\Config\Options;

use function in_array;

/**
 * @internal
 *
 * @psalm-import-type MergePlanType from MergePlan
 */
final class MergePlanCollector
{
    private const PACKAGES_ORDER = [
        Options::VENDOR_OVERRIDE_PACKAGE_NAME => 1,
        Options::ROOT_PACKAGE_NAME => 2,
    ];

    /**
     * @psalm-var MergePlanType
     */
    private array $mergePlan = [];

    /**
     * @psalm-var array<string,true|null>
     */
    private array $processedGroups = [];

    /**
     * Adds an item to the merge plan.
     *
     * @param array|string $file The config file.
     * @param string $package The package name.
     * @param string $group The group name.
     *
     * @psalm-param list{string,string}|string $file
     */
    public function add(array|string $file, string $package, string $group): void
    {
        $this->mergePlan[$group][$package][] = $file;
    }

    /**
     * Adds a multiple items to the merge plan.
     *
     * @param array $files The config files.
     * @param string $package The package name.
     * @param string $group The group name.
     *
     * @psalm-param list<string|list{string,string}> $files
     */
    public function addMultiple(
        array $files,
        string $package,
        string $group,
    ): void {
        $this->mergePlan[$group][$package] = $files;
    }

    /**
     * Add empty group if it doesn't exist.
     *
     * @param string $group The group name.
     */
    public function addGroup(string $group): void
    {
        if (!isset($this->mergePlan[$group])) {
            $this->mergePlan[$group] = [];
        }
    }

    /**
     * Returns the merge plan as an array.
     *
     * @psalm-return MergePlanType
     */
    public function asArray(): array
    {
        $groups = [];
        foreach ($this->mergePlan as $group => $packages) {
            $groups[$group] = $this->expandVariablesInPackages($packages, $this->mergePlan);
        }

        $environments = [];
        foreach ($groups as $packages) {
            foreach ($packages as $files) {
                foreach ($files as $file) {
                    if (is_array($file)) {
                        $environments[$file[0]] = true;
                    }
                }
            }
        }

        return [
            'groups' => $groups,
            'environments' => array_keys($environments),
        ];
    }

    /**
     * @psalm-param array<string, string[]> $packages
     * @psalm-param array<string, array<string, string[]>> $groups
     * @psalm-return array<string, string[]>
     */
    private function expandVariablesInPackages(array $packages, array $groups, ?string $targetGroup = null): array
    {
        if ($targetGroup !== null) {
            if (!isset($groups[$targetGroup])) {
                throw new ErrorException(
                    sprintf('The "%s" configuration group does not exist.', $targetGroup),
                    severity: E_USER_ERROR
                );
            }

            if (isset($this->processedGroups[$targetGroup])) {
                throw new ErrorException('Circular dependency', severity: E_USER_ERROR);
            }
            $this->processedGroups[$targetGroup] = true;
            $groupPackages = $this->expandVariablesInPackages($groups[$targetGroup], $groups);
            $this->processedGroups[$targetGroup] = null;

            $variable = '$' . $targetGroup;
            foreach ($groupPackages as $groupPackage => $groupItems) {
                $packageItems = $packages[$groupPackage] ?? [];
                $packages[$groupPackage] = in_array($variable, $packageItems, true)
                    ? $this->replaceVariableToFiles($packageItems, $variable, $groupItems)
                    : array_merge($packageItems, $groupItems);
            }
            foreach ($packages as $package => $items) {
                $packages[$package] = array_filter(
                    $items,
                    static fn($item) => $item !== $variable,
                );
            }
            uksort(
                $packages,
                static fn(string $a, string $b) => (self::PACKAGES_ORDER[$a] ?? 0) <=> (self::PACKAGES_ORDER[$b] ?? 0),
            );
        }

        foreach ($packages as $items) {
            foreach ($items as $item) {
                if (Options::isVariable($item)) {
                    return $this->expandVariablesInPackages($packages, $groups, substr($item, 1));
                }
            }
        }

        return $packages;
    }

    /**
     * @param string[] $items
     * @param string[] $files
     * @return string[]
     */
    private function replaceVariableToFiles(array $items, string $variable, array $files): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($item === $variable) {
                $result = array_merge($result, $files);
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }
}
