<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Factory;

/**
 * @internal
 * @psalm-type PackageConfigurationType = array<string, string|list<string>>
 * @psalm-type EnvironmentsConfigurationType = array<string, array<string, string|string[]>>
 */
final class RootConfiguration
{
    private string $path;

    private Options $options;

    /**
     * @psalm-var PackageConfigurationType
     */
    private array $packageConfiguration;

    /**
     * @psalm-var EnvironmentsConfigurationType
     */
    private array $environmentsConfiguration;

    public function __construct(Composer $composer)
    {
        $this->path = realpath(dirname(Factory::getComposerFile()));

        $composerExtra = $composer->getPackage()->getExtra();
        if (isset($composerExtra['config-plugin-file'])) {
            /**
             * @var array $extra
             * @psalm-suppress UnresolvableInclude,MixedOperand
             */
            $extra = require $this->path . '/' . $composerExtra['config-plugin-file'];
        } else {
            $extra = $composerExtra;
        }

        $this->options = new Options($extra);

        /** @psalm-var PackageConfigurationType */
        $this->packageConfiguration = (array) ($extra['config-plugin'] ?? []);

        /** @psalm-var EnvironmentsConfigurationType */
        $this->environmentsConfiguration = $extra['config-plugin-environments'] ?? [];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Returns the root package configuration.
     *
     * @return array The root package configuration.
     *
     * @psalm-return PackageConfigurationType
     */
    public function getPackageConfiguration(): array
    {
        return $this->packageConfiguration;
    }

    /**
     * Returns the environment configuration.
     *
     * @return array The environment configuration.
     *
     * @psalm-return EnvironmentsConfigurationType
     */
    public function getEnvironmentsConfiguration(): array
    {
        return $this->environmentsConfiguration;
    }
}
