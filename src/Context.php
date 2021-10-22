<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class Context
{
    private const VENDOR = 1;
    private const APPLICATION = 2;
    private const ENVIRONMENT = 3;

    private string $group;
    private int $level;
    private string $file;
    private bool $isVariable;

    public function __construct(string $environment, string $group, string $package, string $file, bool $isVariable)
    {
        $this->group = $group;
        $this->level = $this->detectLevel($environment, $package);
        $this->file = $file;
        $this->isVariable = $isVariable;
    }

    public function group(): string
    {
        return $this->group;
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

    private function detectLevel(string $environment, string $package): int
    {
        if ($package !== Options::ROOT_PACKAGE_NAME) {
            return self::VENDOR;
        }

        if ($environment === Options::DEFAULT_ENVIRONMENT) {
            return self::APPLICATION;
        }

        return self::ENVIRONMENT;
    }
}
