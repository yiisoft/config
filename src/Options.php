<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function array_filter;
use function is_array;
use function str_replace;
use function strpos;
use function trim;

/**
 * @internal
 */
final class Options
{
    public const MERGE_PLAN_FILENAME = '.merge-plan.php';
    public const DEFAULT_CONFIG_DIRECTORY = '';
    public const DEFAULT_VENDOR_DIRECTORY = 'vendor';
    public const DEFAULT_ENVIRONMENT = '/';
    public const ROOT_PACKAGE_NAME = '/';
    public const OVER_VENDOR_PACKAGE_NAME = '//';

    private bool $buildMergePlan = true;
    private array $overVendorPackages = [];
    private string $sourceDirectory = self::DEFAULT_CONFIG_DIRECTORY;

    public function __construct(array $extra)
    {
        if (!isset($extra['config-plugin-options']) || !is_array($extra['config-plugin-options'])) {
            return;
        }

        $options = $extra['config-plugin-options'];

        if (isset($options['build-merge-plan'])) {
            $this->buildMergePlan = (bool) $options['build-merge-plan'];
        }

        if (isset($options['over-vendor-layer'])) {
            $this->overVendorPackages = (array) $options['over-vendor-layer'];
        }

        if (isset($options['source-directory'])) {
            $this->sourceDirectory = $this->normalizePath((string) $options['source-directory']);
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

    public function buildMergePlan(): bool
    {
        return $this->buildMergePlan;
    }

    public function overVendorPackages(): array
    {
        return $this->overVendorPackages;
    }

    public function sourceDirectory(): string
    {
        return $this->sourceDirectory;
    }

    private function normalizePath(string $value): string
    {
        return trim(str_replace('\\', '/', $value), '/');
    }
}
