<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveGroupsFromVendor;
use Yiisoft\Config\Modifier\RemoveKeysFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;

use function array_flip;
use function array_key_exists;
use function array_merge;
use function array_shift;

/**
 * @internal
 */
final class DataModifiers
{
    /**
     * @psalm-var array<string, array-key>
     */
    private array $recursiveMergeGroupsIndex;

    /**
     * @psalm-var array<string, array-key>
     */
    private array $reverseMergeGroupsIndex;

    /**
     * @psalm-var array<string, true>
     */
    private array $removeFromVendorGroupsIndex = [];

    /**
     * @psalm-var array<string, array<string, mixed>>
     */
    private array $removeFromVendorKeysIndex = [];

    /**
     * @param object[] $modifiers Modifiers that affect merge process.
     */
    public function __construct(array $modifiers = [])
    {
        $reverseMergeGroups = [];
        $recursiveMergeGroups = [];

        foreach ($modifiers as $modifier) {
            if ($modifier instanceof ReverseMerge) {
                array_unshift($reverseMergeGroups, $modifier->getGroups());
                continue;
            }

            if ($modifier instanceof RecursiveMerge) {
                array_unshift($recursiveMergeGroups, $modifier->getGroups());
                continue;
            }

            if ($modifier instanceof RemoveGroupsFromVendor) {
                foreach ($modifier->getGroups() as $package => $groups) {
                    foreach ($groups as $group) {
                        $this->removeFromVendorGroupsIndex[$package . '~' . $group] = true;
                    }
                }
                continue;
            }

            if ($modifier instanceof RemoveKeysFromVendor) {
                $configPaths = [];

                if ($modifier->getPackages() === []) {
                    $configPaths[] = '*';
                } else {
                    foreach ($modifier->getPackages() as $configPath) {
                        $package = array_shift($configPath);

                        if ($configPath === []) {
                            $configPaths[] = $package . '~*';
                        } else {
                            foreach ($configPath as $group) {
                                $configPaths[] = $package . '~' . $group;
                            }
                        }
                    }
                }

                foreach ($modifier->getKeys() as $keyPath) {
                    foreach ($configPaths as $configPath) {
                        $this->removeFromVendorKeysIndex[$configPath] ??= [];
                        ArrayHelper::setValue($this->removeFromVendorKeysIndex[$configPath], $keyPath, true);
                    }
                }
            }
        }

        $this->reverseMergeGroupsIndex = array_flip(array_merge(...$reverseMergeGroups));
        $this->recursiveMergeGroupsIndex = array_flip(array_merge(...$recursiveMergeGroups));
    }

    public function isRecursiveMergeGroup(string $group): bool
    {
        return array_key_exists($group, $this->recursiveMergeGroupsIndex);
    }

    public function isReverseMergeGroup(string $group): bool
    {
        return array_key_exists($group, $this->reverseMergeGroupsIndex);
    }

    public function shouldRemoveGroupFromVendor(string $package, string $group, int $layer): bool
    {
        if ($layer !== Context::VENDOR) {
            return false;
        }

        return array_key_exists('*~*', $this->removeFromVendorGroupsIndex)
            || array_key_exists('*~' . $group, $this->removeFromVendorGroupsIndex)
            || array_key_exists($package . '~*', $this->removeFromVendorGroupsIndex)
            || array_key_exists($package . '~' . $group, $this->removeFromVendorGroupsIndex);
    }

    /**
     * @psalm-param non-empty-array<array-key, string> $keyPath
     */
    public function shouldRemoveKeyFromVendor(Context $context, array $keyPath): bool
    {
        if ($context->layer() !== Context::VENDOR) {
            return false;
        }

        $configPaths = [
            '*',
            $context->package() . '~*',
            $context->package() . '~' . $context->group(),
        ];

        foreach ($configPaths as $configPath) {
            if (ArrayHelper::getValue($this->removeFromVendorKeysIndex[$configPath] ?? [], $keyPath) === true) {
                return true;
            }
        }

        return false;
    }
}
