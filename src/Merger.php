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
use function implode;
use function is_array;
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
    private array $recursiveMergeGroupsIndex;
    private array $reverseMergeGroupsIndex;

    /**
     * @psalm-var array<int, array>
     */
    private array $cacheKeys = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param object[] $modifiers Names of config groups that should be merged recursively.
     */
    public function __construct(ConfigPaths $paths, array $modifiers = [])
    {
        $this->paths = $paths;

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

    public function reset(): void
    {
        $this->cacheKeys = [];
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * @param Context $context Context containing the name of the file, package, group and environment.
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param array ...$args Two or more arrays to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function merge(Context $context, array $recursiveKeyPath = [], array ...$args): array
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
                    /** @var array $array */
                    $array = $result[$k] ?? [];
                    $result[$k] = $this->merge(
                        $context,
                        $recursiveKeyPath,
                        $array,
                        $v,
                    );
                    continue;
                }

                $existKey = array_key_exists($k, $result);
                $recursiveKeyPath[] = $k;

                if ($existKey) {
                    /** @var string|null $file */
                    $file = ArrayHelper::getValue(
                        $this->cacheKeys,
                        array_merge([$context->level()], $recursiveKeyPath)
                    );
                    if ($file !== null) {
                        throw new ErrorException(
                            $this->getDuplicateErrorMessage($recursiveKeyPath, [$file, $context->file()]),
                            0,
                            E_USER_ERROR,
                        );
                    }
                }

                if (!$isReverseMerge || !$existKey) {
                    /** @psalm-suppress MixedPropertyTypeCoercion */
                    ArrayHelper::setValue(
                        $this->cacheKeys,
                        array_merge([$context->level()], $recursiveKeyPath),
                        $context->file()
                    );
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
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param string[] $absoulteFilePaths
     *
     * @return string The duplicate key error message.
     */
    private function getDuplicateErrorMessage(array $recursiveKeyPath, array $absoulteFilePaths): string
    {
        $filePaths = array_map(
            fn (string $filePath) => ' - ' . $this->paths->relative($filePath),
            $absoulteFilePaths
        );

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
