<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;

use function array_key_exists;
use function array_flip;
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
    private array $removeFromVendorKeysIndex;

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
        $this->removeFromVendorKeysIndex = [];
        foreach ($modifiers as $modifier) {
            if ($modifier instanceof ReverseMerge) {
                $reverseMergeGroups = array_merge($reverseMergeGroups, $modifier->getGroups());
            }
            if ($modifier instanceof RecursiveMerge) {
                $recursiveMergeGroups = array_merge($recursiveMergeGroups, $modifier->getGroups());
            }
            if ($modifier instanceof RemoveFromVendor) {
                foreach ($modifier->getKeys() as $path) {
                    ArrayHelper::setValue($this->removeFromVendorKeysIndex, $path, true);
                }
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
     * @param array $arrayA First array to merge.
     * @param array $arrayB Second array to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function merge(Context $context, array $arrayA, array $arrayB): array
    {
        $isRecursiveMerge = array_key_exists($context->group(), $this->recursiveMergeGroupsIndex);
        $isReverseMerge = array_key_exists($context->group(), $this->reverseMergeGroupsIndex);

        if ($isReverseMerge) {
            $arrayB = $this->prepareArrayForReverse($context, [], $arrayB, $isRecursiveMerge);
        }

        return $this->performMerge(
            $context,
            [],
            $isReverseMerge ? $arrayB : $arrayA,
            $isReverseMerge ? $arrayA : $arrayB,
            $isRecursiveMerge,
            $isReverseMerge,
        );
    }

    /**
     * @param Context $context Context containing the name of the file, package, group and environment.
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param array $arrayA First array to merge.
     * @param array $arrayB Second array to merge.
     * @param bool $isRecursiveMerge
     * @param bool $isReverseMerge
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function performMerge(
        Context $context,
        array $recursiveKeyPath,
        array $arrayA,
        array $arrayB,
        bool $isRecursiveMerge,
        bool $isReverseMerge
    ): array {
        $result = $arrayA;

        /** @psalm-var mixed $v */
        foreach ($arrayB as $k => $v) {
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

            $fullKeyPath = array_merge($recursiveKeyPath, [$k]);

            if (
                $isRecursiveMerge
                && is_array($v)
                && (
                    !array_key_exists($k, $result)
                    || is_array($result[$k])
                )
            ) {
                /** @var array $array */
                $array = $result[$k] ?? [];
                $this->setValue(
                    $context,
                    $fullKeyPath,
                    $result,
                    $k,
                    $this->performMerge($context, $fullKeyPath, $array, $v, $isRecursiveMerge, $isReverseMerge)
                );
                continue;
            }

            $existKey = array_key_exists($k, $result);

            if ($existKey && !$isReverseMerge) {
                /** @var string|null $file */
                $file = ArrayHelper::getValue(
                    $this->cacheKeys,
                    array_merge([$context->level()], $fullKeyPath)
                );
                if ($file !== null) {
                    throw new ErrorException(
                        $this->getDuplicateErrorMessage($fullKeyPath, [$file, $context->file()]),
                        0,
                        E_USER_ERROR,
                    );
                }
            }

            if (!$isReverseMerge || !$existKey) {
                $isSet = $this->setValue($context, $fullKeyPath, $result, $k, $v);
                if ($isSet && !$isReverseMerge && !$context->isVariable()) {
                    /** @psalm-suppress MixedPropertyTypeCoercion */
                    ArrayHelper::setValue(
                        $this->cacheKeys,
                        array_merge([$context->level()], $fullKeyPath),
                        $context->file()
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param string[] $recursiveKeyPath
     */
    private function prepareArrayForReverse(Context $context, array $recursiveKeyPath, array $array, bool $isRecursiveMerge): array
    {
        $result = [];

        /** @var mixed $value */
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[$key] = $value;
                continue;
            }

            if (
                $context->isVendor()
                && ArrayHelper::getValue(
                    $this->removeFromVendorKeysIndex,
                    array_merge($recursiveKeyPath, [$key])
                ) === true
            ) {
                continue;
            }

            if (
                $isRecursiveMerge
                && is_array($value)
            ) {
                $result[$key] = $this->prepareArrayForReverse(
                    $context,
                    array_merge($recursiveKeyPath, [$key]),
                    $value,
                    $isRecursiveMerge
                );
                continue;
            }

            if ($context->isVariable()) {
                $result[$key] = $value;
                continue;
            }

            $recursiveKeyPath[] = $key;

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

            $result[$key] = $value;

            /** @psalm-suppress MixedPropertyTypeCoercion */
            ArrayHelper::setValue(
                $this->cacheKeys,
                array_merge([$context->level()], $recursiveKeyPath),
                $context->file()
            );
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @psalm-param non-empty-array<array-key, string> $keyPath
     */
    private function setValue(Context $context, array $keyPath, array &$array, string $key, $value): bool
    {
        if (
            $context->isVendor()
            && ArrayHelper::getValue($this->removeFromVendorKeysIndex, $keyPath) === true
        ) {
            return false;
        }

        /** @var mixed */
        $array[$key] = $value;

        return true;
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
