<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function is_array;
use function str_replace;
use function strpos;
use function trim;

/**
 * @internal
 */
final class Options
{
    public const MERGE_PLAN_FILENAME = 'merge_plan.php';
    public const DEFAULT_CONFIGS_PATH = 'config/packages';
    public const CONFIG_PACKAGE_PRETTY_NAME = 'yiisoft/config';

    private bool $silentOverride = false;
    private bool $forceCheck = false;
    private string $sourceDirectory = '/';
    private string $outputDirectory = '/' . self::DEFAULT_CONFIGS_PATH;

    public function __construct(array $extra)
    {
        if (!isset($extra['config-plugin-options']) || !is_array($extra['config-plugin-options'])) {
            return;
        }

        $options = $extra['config-plugin-options'];

        if (isset($options['silent-override'])) {
            $this->silentOverride = (bool) $options['silent-override'];
        }

        if (isset($options['force-check'])) {
            $this->forceCheck = (bool) $options['force-check'];
        }

        if (isset($options['source-directory'])) {
            $this->sourceDirectory = $this->normalizeRelativePath((string) $options['source-directory']);
        }

        if (isset($options['output-directory'])) {
            $this->outputDirectory = $this->normalizeRelativePath((string) $options['output-directory']);
        }
    }

    public static function containsWildcard(string $file): bool
    {
        return strpos($file, '*') !== false;
    }

    public static function isOptional(string $file): bool
    {
        return strpos($file, '?') === 0;
    }

    public static function isVariable(string $file): bool
    {
        return strpos($file, '$') === 0;
    }

    public function silentOverride(): bool
    {
        return $this->silentOverride;
    }

    public function forceCheck(): bool
    {
        return $this->forceCheck;
    }

    public function sourceDirectory(): string
    {
        return $this->sourceDirectory;
    }

    public function outputDirectory(): string
    {
        return $this->outputDirectory;
    }

    private function normalizeRelativePath(string $value): string
    {
        return '/' . trim(str_replace('\\', '/', $value), '/');
    }
}
