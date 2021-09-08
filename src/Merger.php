<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function array_key_exists;
use function array_flip;
use function array_shift;
use function array_map;
use function file_get_contents;
use function implode;
use function is_array;
use function is_file;
use function is_int;
use function sprintf;
use function substr_count;
use function usort;

/**
 * @internal
 */
final class Merger
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private array $recursiveMergeGroupsIndex;


    /**
     * @var array<string, array<string, string>>
     */
    private array $rootGroupMergedKeys = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param MergePlan $mergePlan The merge plan instance.
     * @param string[] $recursiveMergeGroups Names of config groups that should be merged recursively.
     */
    public function __construct(ConfigPaths $paths, MergePlan $mergePlan, array $recursiveMergeGroups = [])
    {
        $this->paths = $paths;
        $this->mergePlan = $mergePlan;
        $this->recursiveMergeGroupsIndex = array_flip($recursiveMergeGroups);
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * @param Context $context Context containing the name of the file, package, group and environment.
     * @param string $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param array ...$args Two or more arrays to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function merge(Context $context, string $recursiveKeyPath = '', array ...$args): array
    {
        $result = array_shift($args) ?: [];

        while (!empty($args)) {
            /** @psalm-var mixed $v */
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $result) && $result[$k] !== $v) {
                        /** @var mixed */
                        $result[] = $v;
                    } else {
                        /** @var mixed */
                        $result[$k] = $v;
                    }
                    continue;
                }

                if (
                    isset($result[$k]) &&
                    is_array($result[$k]) &&
                    is_array($v) &&
                    array_key_exists($context->group(), $this->recursiveMergeGroupsIndex)
                ) {
                    $result[$k] = $this->merge(
                        $context,
                        $recursiveKeyPath ? $recursiveKeyPath . ' => ' . $k : $k,
                        $result[$k],
                        $v,
                    );
                    continue;
                }

                if (
                    array_key_exists($k, $result) && (
                        $context->package() !== Options::ROOT_PACKAGE_NAME ||
                        isset($this->rootGroupMergedKeys[$context->group()][$k])
                    )
                ) {
                    throw new ErrorException(
                        $this->getDuplicateErrorMessage($k, $recursiveKeyPath, $context),
                        0,
                        E_USER_ERROR,
                    );
                }

                if ($context->package() === Options::ROOT_PACKAGE_NAME && !Options::isVariable($context->file())) {
                    $this->rootGroupMergedKeys[$context->group()][$k] = $context->file();
                }
                /** @var mixed */
                $result[$k] = $v;
            }
        }

        return $result;
    }

    /**
     * Returns a duplicate key error message.
     *
     * @param string $key The duplicate key.
     * @param string $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param Context $context Context containing the name of the file, package, group and environment.
     *
     * @return string The duplicate key error message.
     */
    private function getDuplicateErrorMessage(string $key, string $recursiveKeyPath, Context $context): string
    {
        if (isset($this->rootGroupMergedKeys[$context->group()][$key])) {
            return $this->formatDuplicateErrorMessage($key, $recursiveKeyPath, [
                $this->paths->relative($context->file(), $context->package()),
                $this->paths->relative($this->rootGroupMergedKeys[$context->group()][$key]),
            ]);
        }

        $filePaths = [$this->paths->relative($context->file(), $context->package())];
        $group = $this->mergePlan->getGroup($context->group(), $context->environment());
        unset($group[$context->package()]);

        foreach ($group as $package => $files) {
            foreach ($files as $file) {
                if (Options::isVariable($file)) {
                    continue;
                }

                if (Options::isOptional($file)) {
                    $file = substr($file, 1);
                }

                $absoluteFilePath = $this->paths->absolute($file, $package);

                if (is_file($absoluteFilePath) && ($fileContent = file_get_contents($absoluteFilePath)) !== false) {
                    if (strpos($fileContent, $key) !== false) {
                        $filePaths[] = $this->paths->relative($file, $package);
                    }
                }
            }
        }

        return $this->formatDuplicateErrorMessage($key, $recursiveKeyPath, $filePaths);
    }

    /**
     * Formats a duplicate key error message.
     *
     * @param string $key The duplicate key.
     * @param string $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param string[] $filePaths The paths to the files where duplicate keys were found.
     *
     * @return string The formatted duplicate key error message.
     */
    private function formatDuplicateErrorMessage(string $key, string $recursiveKeyPath, array $filePaths): string
    {
        $filePaths = array_map(static fn (string $filePath) => ' - ' . $filePath, $filePaths);

        usort($filePaths, static function (string $a, string $b) {
            $countDirsA = substr_count($a, '/');
            $countDirsB = substr_count($b, '/');
            return $countDirsA === $countDirsB ? $a <=> $b : $countDirsA <=> $countDirsB;
        });

        return sprintf(
            "Duplicate key \"%s\" in configs:\n%s",
            $recursiveKeyPath ? $recursiveKeyPath . ' => ' . $key : $key,
            implode("\n", $filePaths),
        );
    }
}
