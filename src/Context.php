<?php

declare(strict_types=1);

namespace Yiisoft\Config;

final class Context
{
    public const VENDOR = 1;
    public const APPLICATION = 2;
    public const ENVIRONMENT = 3;

    private string $group;
    private int $level;
    private string $file;

    public function __construct(string $environment, string $group, string $package, string $file)
    {
        $this->group = $group;
        $this->level = $this->detectLevel($environment, $package);
        $this->file = $file;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function file(): string
    {
        return $this->file;
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
