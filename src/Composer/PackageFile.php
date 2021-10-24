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
    private string $absolutePath;

    public function __construct(string $filename, string $relativePath, string $absolutePath)
    {
        $this->filename = $filename;
        $this->relativePath = $relativePath;
        $this->absolutePath = $absolutePath;
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
