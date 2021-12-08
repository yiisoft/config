<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use Yiisoft\Config\Modifier\RemoveGroupsFromVendor;

use function array_key_exists;
use function array_merge;
use function glob;
use function is_file;
use function sprintf;
use function substr;

/**
 * @internal
 */
final class FilesExtractor
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private string $environment;

    /**
     * @psalm-var array<string,true>
     */
    private array $removeFromVendorGroupsIndex;

    /**
     * @param object[] $modifiers Modifiers that affect merge process.
     */
    public function __construct(
        ConfigPaths $paths,
        MergePlan $mergePlan,
        string $environment,
        array $modifiers
    ) {
        $this->paths = $paths;
        $this->mergePlan = $mergePlan;
        $this->environment = $environment;

        $this->removeFromVendorGroupsIndex = [];
        foreach ($modifiers as $modifier) {
            if ($modifier instanceof RemoveGroupsFromVendor) {
                foreach ($modifier->getGroups() as $package => $groupNames) {
                    foreach ($groupNames as $groupName) {
                        $this->removeFromVendorGroupsIndex[$package . '~' . $groupName] = true;
                    }
                }
            }
        }
    }

    /**
     * Extracts group configuration data from files.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If an error occurred during the extract.
     *
     * @psalm-return array<string, Context>
     */
    public function extract(string $group): array
    {
        $environment = $this->prepareEnvironment($group);

        $result = $this->process(Options::DEFAULT_ENVIRONMENT, $group, $this->mergePlan->getGroup($group));

        if ($environment !== Options::DEFAULT_ENVIRONMENT) {
            $result = array_merge(
                $result,
                $this->process(
                    $environment,
                    $group,
                    $this->mergePlan->getGroup($group, $environment)
                )
            );
        }

        return $result;
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
        return $this->mergePlan->hasGroup($group, $this->environment) || (
            $this->environment !== Options::DEFAULT_ENVIRONMENT &&
            $this->mergePlan->hasGroup($group, Options::DEFAULT_ENVIRONMENT)
        );
    }

    /**
     * @psalm-param array<string, string[]> $data
     *
     * @throws ErrorException If an error occurred during the process.
     *
     * @psalm-return array<string, Context>
     */
    private function process(string $environment, string $group, array $data): array
    {
        $result = [];

        foreach ($data as $package => $items) {
            $level = $this->detectLevel($environment, $package);

            if (
                $level === Context::VENDOR
                && (
                    array_key_exists('*~*', $this->removeFromVendorGroupsIndex)
                    || array_key_exists('*~' . $group, $this->removeFromVendorGroupsIndex)
                    || array_key_exists($package . '~*', $this->removeFromVendorGroupsIndex)
                    || array_key_exists($package . '~' . $group, $this->removeFromVendorGroupsIndex)
                )
            ) {
                continue;
            }

            foreach ($items as $item) {
                if (Options::isVariable($item)) {
                    $result[$item] = new Context($group, $package, $level, $item, true);
                    continue;
                }

                $isOptional = Options::isOptional($item);

                if ($isOptional) {
                    $item = substr($item, 1);
                }

                $filePath = $this->paths->absolute($item, $package);
                $files = Options::containsWildcard($item) ? glob($filePath) : [$filePath];

                foreach ($files as $file) {
                    if (is_file($file)) {
                        $result[$file] = new Context($group, $package, $level, $file, false);
                    } elseif (!$isOptional) {
                        $this->throwException(sprintf('The "%s" file does not found.', $file));
                    }
                }
            }
        }

        return $result;
    }

    private function detectLevel(string $environment, string $package): int
    {
        if ($package !== Options::ROOT_PACKAGE_NAME) {
            return Context::VENDOR;
        }

        if ($environment === Options::DEFAULT_ENVIRONMENT) {
            return Context::APPLICATION;
        }

        return Context::ENVIRONMENT;
    }

    /**
     * Checks the group name and returns actual environment name.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If the group does not exist.
     *
     * @return string The actual environment name.
     */
    private function prepareEnvironment(string $group): string
    {
        if (!$this->mergePlan->hasGroup($group, $this->environment)) {
            if (
                $this->environment === Options::DEFAULT_ENVIRONMENT ||
                !$this->mergePlan->hasGroup($group, Options::DEFAULT_ENVIRONMENT)
            ) {
                $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
            }

            return Options::DEFAULT_ENVIRONMENT;
        }

        return $this->environment;
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
