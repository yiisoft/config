<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Composer;
use Composer\Factory;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;

use function array_map;
use function dirname;
use function file_exists;
use function glob;
use function in_array;
use function realpath;
use function str_replace;
use function substr;

/**
 * @internal
 */
final class ComposerConfigProcess
{
    private Composer $composer;
    private string $configsDirectory;
    private string $rootPath;

    /**
     * @var ConfigFile[]
     */
    private array $configFiles = [];

    /**
     * @psalm-var array<string, array<string, string|list<string>>>
     */
    private array $mergePlan = [];

    /**
     * @param Composer $composer The composer instance.
     * @param array $packagesForCheck Pretty package names to check.
     * @param bool|null $forceCheck Forced packages check.
     */
    public function __construct(Composer $composer, array $packagesForCheck, ?bool $forceCheck = null)
    {
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $rootPackage = $composer->getPackage();
        $rootOptions = new Options($rootPackage->getExtra());
        $forceCheck ??= $rootOptions->forceCheck()
            || in_array(Options::CONFIG_PACKAGE_PRETTY_NAME, $packagesForCheck, true)
        ;

        /** @psalm-suppress MixedArgument */
        $this->rootPath = realpath(dirname(Factory::getComposerFile()));
        $this->configsDirectory = $rootOptions->outputDirectory();
        $this->composer = $composer;

        $this->process($rootOptions, $packagesForCheck, $forceCheck);
        $this->appendRootPackageConfigToMergePlan($rootPackage, $rootOptions);
    }

    /**
     * Returns the configuration files to change.
     *
     * @return ConfigFile[] The configuration files to change.
     */
    public function configFiles(): array
    {
        return $this->configFiles;
    }

    /**
     * Returns data for changing the merge plan.
     *
     * @return array Data for changing the merge plan.
     */
    public function mergePlan(): array
    {
        return $this->mergePlan;
    }

    /**
     * Returns the name of the directory containing the configuration files.
     *
     * @return string The name of the directory containing the configuration files.
     */
    public function configsDirectory(): string
    {
        return $this->configsDirectory;
    }

    /**
     * Returns the full path to the directory containing composer.json.
     *
     * @return string The full path to directory containing composer.json.
     */
    public function rootPath(): string
    {
        return $this->rootPath;
    }

    private function process(Options $rootOptions, array $packagesForCheck, bool $forceCheck): void
    {
        foreach ((new PackagesListBuilder($this->composer))->build() as $package) {
            $options = new Options($package->getExtra());

            foreach ($this->getPackageConfig($package) as $group => $files) {
                $files = (array) $files;

                foreach ($files as $file) {
                    $isOptional = false;

                    if (Options::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    // Do not copy variables.
                    if (Options::isVariable($file)) {
                        $this->mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    $checkFileOnUpdate = $forceCheck || in_array($package->getPrettyName(), $packagesForCheck, true);
                    $sourceFilePath = $this->getPackageSourcePath($package, $options) . '/' . $file;

                    if (Options::containsWildcard($file)) {
                        $matches = glob($sourceFilePath);

                        if ($isOptional && $matches === []) {
                            continue;
                        }

                        if ($checkFileOnUpdate) {
                            foreach ($matches as $match) {
                                $relativePath = str_replace($this->getPackageSourcePath($package, $options) . '/', '', $match);
                                $this->configFiles[] = new ConfigFile($match, $package->getPrettyName() . '/' . $relativePath);
                            }
                        }

                        $this->mergePlan[$group][$package->getPrettyName()][] = $file;
                        continue;
                    }

                    if ($isOptional && !file_exists($sourceFilePath)) {
                        // Skip it in both copying and final config.
                        continue;
                    }

                    if ($checkFileOnUpdate) {
                        $this->configFiles[] = new ConfigFile(
                            $sourceFilePath,
                            $package->getPrettyName() . '/' . $file,
                            $rootOptions->silentOverride(),
                        );
                    }

                    $this->mergePlan[$group][$package->getPrettyName()][] = $file;
                }
            }
        }
    }

    private function appendRootPackageConfigToMergePlan(RootPackageInterface $package, Options $options): void
    {
        foreach ($this->getPackageConfig($package) as $group => $files) {
            $files = array_map(static function ($file) use ($options): string {
                if (Options::isVariable($file)) {
                    return $file;
                }

                $isOptional = Options::isOptional($file);
                $result = $isOptional ? '?' : '';

                if ($isOptional) {
                    $file = substr($file, 1);
                }

                if ($options->sourceDirectory() !== '') {
                    $result .= $options->sourceDirectory() . '/';
                }

                return $result . $file;
            }, (array) $files);

            /** @psalm-suppress PropertyTypeCoercion */
            $this->mergePlan[$group] = ['/' => $files] + ($this->mergePlan[$group] ?? []);
        }
    }

    private function getPackageSourcePath(PackageInterface $package, Options $options): string
    {
        return $this->composer->getInstallationManager()->getInstallPath($package)
            . ($options->sourceDirectory() === '' ? '' : '/' . $options->sourceDirectory())
        ;
    }

    /**
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getPackageConfig(PackageInterface $package): array
    {
        return $package->getExtra()['config-plugin'] ?? [];
    }
}
