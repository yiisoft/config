<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Config\Modifier\RecursiveMerge;

use Yiisoft\Config\Modifier\ReverseMerge;

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
    private array $reverseMergeGroupsIndex;

    /**
     * @psalm-var array<string, array<int, array>>
     */
    private array $cacheKeys = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param MergePlan $mergePlan The merge plan instance.
     * @param object[] $modifiers Names of config groups that should be merged recursively.
     */
    public function __construct(ConfigPaths $paths, MergePlan $mergePlan, array $modifiers = [])
    {
        $this->paths = $paths;
        $this->mergePlan = $mergePlan;

        $reverseMergeGroups = [];
        $recursiveMergeGroups = [];
        foreach ($modifiers as $modifier) {
            if ($modifier instanceof ReverseMerge) {
                $reverseMergeGroups = array_merge($reverseMergeGroups, $modifier->getGroups());
            }
            if ($modifier instanceof RecursiveMerge) {
                $recursiveMergeGroups = array_merge($recursiveMergeGroups, $modifier->getGroups());
            }
        }

        $this->reverseMergeGroupsIndex = array_flip($reverseMergeGroups);
        $this->recursiveMergeGroupsIndex = array_flip($recursiveMergeGroups);
    }

    public function prepare(): void
    {
        $this->cacheKeys = [];
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * @param NewContext $context Context containing the name of the file, package, group and environment.
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param array ...$args Two or more arrays to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function merge(NewContext $context, array $recursiveKeyPath = [], array ...$args): array
    {
        if ($args === []) {
            return [];
        }

        $isReverseMerge = array_key_exists($context->group(), $this->reverseMergeGroupsIndex);
        $isRecursiveMerge = array_key_exists($context->group(), $this->recursiveMergeGroupsIndex);

        $result = $isReverseMerge ? array_pop($args) : array_shift($args);

        while (!empty($args)) {
            /** @psalm-var mixed $v */
            foreach ($isReverseMerge ? array_pop($args) : array_shift($args) as $k => $v) {
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
                    $isRecursiveMerge
                    && is_array($v)
                    && (
                        !array_key_exists($k, $result)
                        || is_array($result[$k])
                    )
                ) {
                    $recursiveKeyPath[] = $k;
                    $result[$k] = $this->merge(
                        $context,
                        $recursiveKeyPath,
                        $result[$k] ?? [],
                        $v,
                    );
                    continue;
                }

                $existKey = array_key_exists($k, $result);
                $recursiveKeyPath[] = $k;

                if (
                    $existKey
                    && ($x = ArrayHelper::getValue($this->cacheKeys, array_merge([$context->level()], $recursiveKeyPath))) !== null
                ) {
                    throw new ErrorException(
                        $this->getDuplicateErrorMessage($recursiveKeyPath, [$x, $context->file()]),
                        0,
                        E_USER_ERROR,
                    );
                }

                if (!$isReverseMerge || !$existKey) {
                    ArrayHelper::setValue($this->cacheKeys, array_merge([$context->level()], $recursiveKeyPath), $context->file());
                    /** @var mixed */
                    $result[$k] = $v;
                }
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
    private function getDuplicateErrorMessage(array $recursiveKeyPath, array $context): string
    {
        $context = array_map(function($f) {
            return $this->paths->myr($f);
        }, $context);
        return $this->formatDuplicateErrorMessage($recursiveKeyPath, $context);

        if (isset($this->rootPackageMergedKeys[$context->environment()][$context->group()][$key])) {
            return $this->formatDuplicateErrorMessage($key, $recursiveKeyPath, [
                $this->paths->relative($context->file(), $context->package()),
                $this->paths->relative($this->rootPackageMergedKeys[$context->environment()][$context->group()][$key]),
            ]);
        }

        $filePaths = [$this->paths->relative($context->file(), $context->package())];
        $group = $this->mergePlan->getGroup($context->group(), $context->environment());
        unset($group[$context->package()], $group[Options::ROOT_PACKAGE_NAME]);

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
    private function formatDuplicateErrorMessage(array $recursiveKeyPath, array $filePaths): string
    {
        $filePaths = array_map(static fn (string $filePath) => ' - ' . $filePath, $filePaths);

        usort($filePaths, static function (string $a, string $b) {
            $countDirsA = substr_count($a, '/');
            $countDirsB = substr_count($b, '/');
            return $countDirsA === $countDirsB ? $a <=> $b : $countDirsA <=> $countDirsB;
        });

        return sprintf(
            "Duplicate key \"%s\" in configs:\n%s",
            implode(' => ', $recursiveKeyPath),
            implode("\n", $filePaths),
        );
    }
}
