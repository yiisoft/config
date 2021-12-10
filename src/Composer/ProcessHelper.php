<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Options;

use function dirname;
use function realpath;
use function str_replace;

/**
 * @internal
 */
final class ProcessHelper
{
    private Composer $composer;
    private ConfigPaths $paths;
    private Options $rootPackageOptions;
    private array $rootPackageExtra;

    /**
     * @param Composer $composer The composer instance.
     */
    public function __construct(Composer $composer)
    {
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';
        /** @psalm-suppress MixedArgument */
        $rootPath = realpath(dirname(Factory::getComposerFile()));

        $rootPackageExtra = $composer->getPackage()->getExtra();
        /** @psalm-suppress UnresolvableInclude */
        $this->rootPackageExtra = isset($rootPackageExtra['config-plugin-file'])
            ? (array) (require_once "$rootPath/{$rootPackageExtra['config-plugin-file']}")
            : $rootPackageExtra
        ;

        $this->composer = $composer;
        $this->rootPackageOptions = new Options($this->rootPackageExtra);
        $this->paths = new ConfigPaths($rootPath, $this->rootPackageOptions->sourceDirectory());
    }

    /**
     * Builds and returns packages.
     *
     * @return array<string, CompletePackage>
     */
    public function buildPackages(): array
    {
        return (new PackagesListBuilder($this->composer))->build();
    }

    /**
     * Returns the absolute path to the package file.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     * @param string $filename The package configuration filename.
     *
     * @return string The absolute path to the package file.
     */
    public function getAbsolutePackageFilePath(PackageInterface $package, Options $options, string $filename): string
    {
        return "{$this->getPackageSourceDirectoryPath($package, $options)}/$filename";
    }

    /**
     * Returns the relative path to the package file including the source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param string $filePath The absolute path to the package file.
     *
     * @return string The relative path to the package file including the source directory.
     */
    public function getRelativePackageFilePath(PackageInterface $package, string $filePath): string
    {
        return str_replace("{$this->getPackageRootDirectoryPath($package)}/", '', $filePath);
    }

    /**
     * Returns the package filename excluding the source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     * @param string $filePath The absolute path to the package file.
     *
     * @return string The package filename excluding the source directory.
     */
    public function getPackageFilename(PackageInterface $package, Options $options, string $filePath): string
    {
        return str_replace("{$this->getPackageSourceDirectoryPath($package, $options)}/", '', $filePath);
    }

    /**
     * Returns the package configuration.
     *
     * @param PackageInterface $package The package instance.
     *
     * @return array The package configuration.
     *
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getPackageConfig(PackageInterface $package): array
    {
        return (array) ($package->getExtra()['config-plugin'] ?? []);
    }

    /**
     * Returns the root package configuration.
     *
     * @return array The root package configuration.
     *
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getRootPackageConfig(): array
    {
        return (array) ($this->rootPackageExtra['config-plugin'] ?? []);
    }

    /**
     * Returns the environment configuration.
     *
     * @return array The environment configuration.
     *
     * @psalm-return array<string, array<string, string|string[]>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getEnvironmentConfig(): array
    {
        return (array) ($this->rootPackageExtra['config-plugin-environments'] ?? []);
    }

    /**
     * Returns the config paths instance.
     *
     * @return ConfigPaths The config paths instance.
     */
    public function getPaths(): ConfigPaths
    {
        return $this->paths;
    }

    /**
     * Checks whether to build a merger plan.
     *
     * @return bool Whether to build a merger plan.
     */
    public function shouldBuildMergePlan(): bool
    {
        return $this->rootPackageOptions->buildMergePlan();
    }

    /**
     * Returns the absolute path to the package source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     *
     * @return string The absolute path to the package config directory.
     */
    private function getPackageSourceDirectoryPath(PackageInterface $package, Options $options): string
    {
        $packageConfigDirectory = $options->sourceDirectory() === '' ? '' : "/{$options->sourceDirectory()}";
        return $this->getPackageRootDirectoryPath($package) . $packageConfigDirectory;
    }

    /**
     * Returns the absolute path to the package root directory.
     *
     * @param PackageInterface $package The package instance.
     *
     * @return string The absolute path to the package root directory.
     */
    private function getPackageRootDirectoryPath(PackageInterface $package): string
    {
        return $this->composer->getInstallationManager()->getInstallPath($package);
    }
}
