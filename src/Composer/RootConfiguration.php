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
    private Options $options;

    /**
     * @psalm-var PackageConfigurationType
     */
    private array $packageConfiguration;

    /**
     * @psalm-var EnvironmentsConfigurationType
     */
    private array $environmentsConfiguration;

    private function __construct(
        private string $path,
        array $composerExtra,
    ) {
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

    public static function fromComposerInstance(Composer $composer): self
    {
        return new self(
            realpath(dirname(Factory::getComposerFile())),
            $composer->getPackage()->getExtra(),
        );
    }

    public function path(): string
    {
        return $this->path;
    }

    public function options(): Options
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
    public function packageConfiguration(): array
    {
        return $this->packageConfiguration;
    }

    /**
     * Returns the environments configuration.
     *
     * @return array The environments configuration.
     *
     * @psalm-return EnvironmentsConfigurationType
     */
    public function environmentsConfiguration(): array
    {
        return $this->environmentsConfiguration;
    }
}
