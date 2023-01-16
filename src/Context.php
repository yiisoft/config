<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class Context
{
    public const VENDOR = 1;
    public const VENDOR_OVERRIDE = 2;
    public const APPLICATION = 3;
    public const ENVIRONMENT = 4;

    public function __construct(
        private string $group,
        private string $package,
        private int $layer,
        private string $file,
        private bool $isVariable,
    ) {
    }

    public function group(): string
    {
        return $this->group;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function layer(): int
    {
        return $this->layer;
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
