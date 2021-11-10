<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class Context
{
    public const VENDOR = 1;
    public const APPLICATION = 2;
    public const ENVIRONMENT = 3;

    private string $group;
    private string $package;
    private int $level;
    private string $file;
    private bool $isVariable;

    public function __construct(string $group, string $package, int $level, string $file, bool $isVariable)
    {
        $this->group = $group;
        $this->package = $package;
        $this->level = $level;
        $this->file = $file;
        $this->isVariable = $isVariable;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function isVendor(): bool
    {
        return $this->level === self::VENDOR;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function isVariable(): bool
    {
        return $this->isVariable;
    }
}
