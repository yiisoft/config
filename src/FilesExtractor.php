<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

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
        private ?string $environment,
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
        if (!$this->mergePlan->hasGroup($group)) {
            $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
        }

        $result = [];

        foreach ($this->mergePlan->getGroup($group) as $package => $items) {
            $defaultLayer = match ($package) {
                Options::ROOT_PACKAGE_NAME => Context::APPLICATION,
                Options::VENDOR_OVERRIDE_PACKAGE_NAME => Context::VENDOR_OVERRIDE,
                default => Context::VENDOR,
            };

            if (
                $defaultLayer === Context::VENDOR
                && $this->dataModifiers->shouldRemoveGroupFromVendor($package, $group)
            ) {
                continue;
            }

            foreach ($items as $item) {
                if (is_array($item)) {
                    if ($item[0] !== $this->environment) {
                        continue;
                    }
                    $item = $item[1];
                    $layer = Context::ENVIRONMENT;
                } else {
                    $layer = $defaultLayer;
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
                        $message = Options::isVariable($item)
                            ? sprintf('Don\'t allow to use variables in environments. Found variable "%s".', $item)
                            : sprintf('The "%s" file does not found.', $file);
                        $this->throwException($message);
                    }
                }
            }
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
        return $this->mergePlan->hasGroup($group) || (
            $this->environment !== Options::DEFAULT_ENVIRONMENT &&
            $this->mergePlan->hasGroup($group)
        );
    }

    /**
     * @throws ErrorException
     */
    private function throwException(string $message): void
    {
        throw new ErrorException($message, 0, E_USER_ERROR);
    }
}
