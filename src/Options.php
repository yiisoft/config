<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function is_array;

/**
 * @internal
 */
final class Options
{
    private bool $silentOverride;
    private bool $forceCheck;
    private string $sourceDirectory;
    private string $outputDirectory;

    public function __construct(array $extra)
    {
        /** @var mixed */
        $options = $extra['config-plugin-options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $this->silentOverride = (bool)($options['silent-override'] ?? false);
        $this->forceCheck = (bool)($options['force-check'] ?? false);
        $this->sourceDirectory = isset($options['source-directory'])
            ? $this->normalizeRelativePath((string)$options['source-directory'])
            : '/';
        $this->outputDirectory = isset($options['output-directory'])
            ? $this->normalizeRelativePath((string)$options['output-directory'])
            : '/config/packages';
    }

    private function normalizeRelativePath(string $value): string
    {
        return '/' . trim(str_replace('\\', '/', $value), '/');
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
}
