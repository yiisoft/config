<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

/**
 * @internal
 */
final class PackageFile
{
    private string $filename;
    private string $relativePath;

    public function __construct(
        ConfigSettings $configSettings,
        private string $absolutePath,
    ) {
        $this->filename = str_replace($configSettings->configPath() . '/', '', $absolutePath);
        $this->relativePath = str_replace($configSettings->path() . '/', '', $absolutePath);
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function absolutePath(): string
    {
        return $this->absolutePath;
    }
}
