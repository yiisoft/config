<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class ConfigFile
{
    private string $sourceFilePath;
    private string $destinationFile;
    private bool $silentOverride;

    public function __construct(string $sourceFilePath, string $destinationFile, bool $silentOverride = false)
    {
        $this->sourceFilePath = $sourceFilePath;
        $this->destinationFile = $destinationFile;
        $this->silentOverride = $silentOverride;
    }

    public function sourceFilePath(): string
    {
        return $this->sourceFilePath;
    }

    public function destinationFile(): string
    {
        return $this->destinationFile;
    }

    public function silentOverride(): bool
    {
        return $this->silentOverride;
    }
}
