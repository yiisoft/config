<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Yiisoft\Config\Options;
use Yiisoft\VarDumper\VarDumper;

use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_file;
use function ksort;
use function strtr;
use function substr;

/**
 * @internal
 */
final class MergePlanProcess
{
    private MergePlanCollector $mergePlanCollector;
    private ProcessHelper $helper;

    /**
     * @param Composer $composer The composer instance.
     */
    public function __construct(Composer $composer)
    {
        $this->mergePlanCollector = new MergePlanCollector();
        $this->helper = new ProcessHelper($composer);

        if (!$this->helper->shouldBuildMergePlan()) {
            return;
        }

        $this->addPackagesConfigsToMergePlan(false);
        $this->addPackagesConfigsToMergePlan(true);

        $this->addRootPackageConfigToMergePlan();
        $this->addEnvironmentsConfigsToMergePlan();

        $this->updateMergePlan();
    }

    private function addPackagesConfigsToMergePlan(bool $isVendorOverrideLayer): void
    {
        $packages = $isVendorOverrideLayer ? $this->helper->getVendorOverridePackages() : $this->helper->getVendorPackages();

        foreach ($packages as $name => $package) {
            $options = new Options($package->getExtra());
            $packageName = $isVendorOverrideLayer ? Options::VENDOR_OVERRIDE_PACKAGE_NAME : $name;

            foreach ($this->helper->getPackageConfig($package) as $group => $files) {
                $this->mergePlanCollector->addGroup($group);

                foreach ((array) $files as $file) {
                    $isOptional = false;

                    if (Options::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    if (Options::isVariable($file)) {
                        $this->mergePlanCollector->add($file, $packageName, $group);
                        continue;
                    }

                    $absoluteFilePath = $this->helper->getAbsolutePackageFilePath($package, $options, $file);

                    if (Options::containsWildcard($file)) {
                        $matches = glob($absoluteFilePath);

                        if (empty($matches)) {
                            continue;
                        }

                        foreach ($matches as $match) {
                            $this->mergePlanCollector->add(
                                $this->normalizePackageFilePath($package, $match, $isVendorOverrideLayer),
                                $packageName,
                                $group,
                            );
                        }

                        continue;
                    }

                    if ($isOptional && !is_file($absoluteFilePath)) {
                        continue;
                    }

                    $this->mergePlanCollector->add(
                        $this->normalizePackageFilePath($package, $absoluteFilePath, $isVendorOverrideLayer),
                        $packageName,
                        $group,
                    );
                }
            }
        }
    }

    private function addRootPackageConfigToMergePlan(): void
    {
        foreach ($this->helper->getRootPackageConfig() as $group => $files) {
            $this->mergePlanCollector->addMultiple(
                (array) $files,
                Options::ROOT_PACKAGE_NAME,
                $group,
            );
        }
    }

    private function addEnvironmentsConfigsToMergePlan(): void
    {
        foreach ($this->helper->getEnvironmentConfig() as $environment => $groups) {
            if ($environment === Options::DEFAULT_ENVIRONMENT) {
                continue;
            }

            if (empty($groups)) {
                $this->mergePlanCollector->addEnvironmentWithoutConfigs($environment);
                continue;
            }

            foreach ($groups as $group => $files) {
                $this->mergePlanCollector->addMultiple(
                    (array) $files,
                    Options::ROOT_PACKAGE_NAME,
                    $group,
                    $environment,
                );
            }
        }
    }

    private function updateMergePlan(): void
    {
        $mergePlan = $this->mergePlanCollector->asArray();
        ksort($mergePlan);

        $filePath = $this->helper->getPaths()->absolute(
            $this->helper->getMergePlanFile()
        );
        (new Filesystem())->ensureDirectoryExists(dirname($filePath));

        $oldContent = is_file($filePath) ? file_get_contents($filePath) : '';

        $content = '<?php'
            . "\n\ndeclare(strict_types=1);"
            . "\n\n// Do not edit. Content will be replaced."
            . "\nreturn " . VarDumper::create($mergePlan)->export(true) . ";\n";

        if ($this->normalizeLineEndings($oldContent) !== $this->normalizeLineEndings($content)) {
            file_put_contents($filePath, $content, LOCK_EX);
        }
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }

    private function normalizePackageFilePath(
        PackageInterface $package,
        string $absoluteFilePath,
        bool $isVendorOverrideLayer
    ): string {
        if ($isVendorOverrideLayer) {
            return $this->helper->getRelativePackageFilePathWithPackageName($package, $absoluteFilePath);
        }

        return $this->helper->getRelativePackageFilePath($package, $absoluteFilePath);
    }
}
