<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

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
    public function __construct(
        private ConfigPaths $paths,
        private MergePlan $mergePlan,
        private DataModifiers $dataModifiers,
        private string $environment,
    ) {
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

        return $this->process($environment, $group, $this->mergePlan->getGroup($group, $environment));

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
            $defaultLayer = match ($package) {
                Options::ROOT_PACKAGE_NAME => Context::APPLICATION,
                Options::VENDOR_OVERRIDE_PACKAGE_NAME => Context::VENDOR_OVERRIDE,
                default => Context::VENDOR,
            };


            if ($defaultLayer === Context::VENDOR && $this->dataModifiers->shouldRemoveGroupFromVendor($package, $group)) {
                continue;
            }

            foreach ($items as $item) {
                if (is_array($item)) {
                    $item = $item[0];
                    $layer = Context::ENVIRONMENT;
                } else {
                    $layer = $defaultLayer;
                }

                if (Options::isVariable($item)) {
                    $result[$item] = new Context($group, $package, $layer, $item, true);
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
                        $result[$file] = new Context($group, $package, $layer, $file, false);
                    } elseif (!$isOptional) {
                        $this->throwException(sprintf('The "%s" file does not found.', $file));
                    }
                }
            }
        }

        return $result;
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
     * @throws ErrorException
     */
    private function throwException(string $message): void
    {
        throw new ErrorException($message, 0, E_USER_ERROR);
    }
}
