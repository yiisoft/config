<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Package\PackageInterface;

/**
 * @internal
 */
final class ConfigFile
{
    private PackageInterface $package;
    private string $filename;
    private string $sourceFilePath;
    private bool $silentOverride;

    public function __construct(
        PackageInterface $package,
        string $filename,
        string $sourceFilePath,
        bool $silentOverride = false
    ) {
        $this->package = $package;
        $this->filename = $filename;
        $this->sourceFilePath = $sourceFilePath;
        $this->silentOverride = $silentOverride;
    }

    public function package(): PackageInterface
    {
        return $this->package;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function destinationFile(): string
    {
        return "{$this->package->getPrettyName()}/$this->filename";
    }

    public function sourceFilePath(): string
    {
        return $this->sourceFilePath;
    }

    public function silentOverride(): bool
    {
        return $this->silentOverride;
    }
}
