<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class Context
{
    private string $file;
    private string $package;
    private string $group;
    private string $environment;

    /**
     * @param string $file The config file.
     * @param string $package The package name.
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function __construct(string $file, string $package, string $group, string $environment)
    {
        $this->file = $file;
        $this->package = $package;
        $this->group = $group;
        $this->environment = $environment;
    }

    /**
     * Returns the config file.
     *
     * @return string The config file.
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * Returns the package name.
     *
     * @return string The package name.
     */
    public function package(): string
    {
        return $this->package;
    }

    /**
     * Returns the group name.
     *
     * @return string The group name.
     */
    public function group(): string
    {
        return $this->group;
    }

    /**
     * Returns the environment name.
     *
     * @return string The environment name.
     */
    public function environment(): string
    {
        return $this->environment;
    }
}
