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

    public function getSourceFilePath(): string
    {
        return $this->sourceFilePath;
    }

    public function getDestinationFile(): string
    {
        return $this->destinationFile;
    }

    public function isSilentOverride(): bool
    {
        return $this->silentOverride;
    }
}
