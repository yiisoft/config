<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

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
     * Returns the merge plan as an array.
     *
     * @psalm-return MergePlanType
     */
    public function generate(): array
    {
        $result = [];
        foreach ($this->mergePlan as $environment => $groups) {
            if ($environment === Options::DEFAULT_ENVIRONMENT) {
                $result[$environment] = [];
                foreach ($groups as $group => $packages) {
                    $result[$environment][$group] = $this->expandVariablesInPackages($packages, $groups);
                }
            } else {
                $result[$environment] = $groups;
            }
        }
        return $result;
    }

    /**
     * @psalm-param array<string, string[]> $packages
     * @psalm-param array<string, array<string, string[]>> $groups
     * @psalm-return array<string, string[]>
     */
    private function expandVariablesInPackages(array $packages, array $groups, ?string $targetGroup = null): array
    {
        if ($targetGroup !== null) {
            $groupPackages = $this->expandVariablesInPackages($groups[$targetGroup], $groups);
            $variable = '$' . $targetGroup;
            foreach ($groupPackages as $groupPackage => $groupItems) {
                $packageItems = $packages[$groupPackage] ?? [];
                $packages[$groupPackage] = in_array($variable, $packageItems, true)
                    ? $this->replaceVariableToFiles($packageItems, $variable, $groupItems)
                    : array_merge($groupItems, $packageItems);
            }
            foreach ($packages as $package => $items) {
                $packages[$package] = array_filter(
                    $items,
                    static fn($item) => $item !== $variable,
                );
            }
            uksort(
                $packages,
                static fn($a, $b) => (self::PACKAGES_ORDER[$a] ?? 0) <=> (self::PACKAGES_ORDER[$b] ?? 0),
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
