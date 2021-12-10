<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Yiisoft\Config\MergePlan;
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
    private MergePlan $mergePlan;
    private ProcessHelper $helper;

    /**
     * @param Composer $composer The composer instance.
     */
    public function __construct(Composer $composer)
    {
        $this->mergePlan = new MergePlan();
        $this->helper = new ProcessHelper($composer);
        $rootPackage = $composer->getPackage();
        $rootOptions = new Options($composer->getPackage()->getExtra());

        if (!$rootOptions->buildMergePlan()) {
            return;
        }

        $this->addPackagesConfigsToMergePlan();
        $this->addRootPackageConfigToMergePlan($rootPackage, $rootOptions);
        $this->addEnvironmentsConfigsToMergePlan($rootPackage, $rootOptions);
        $this->updateMergePlan();
    }

    private function addPackagesConfigsToMergePlan(): void
    {
        foreach ($this->helper->buildPackages() as $package) {
            $options = new Options($package->getExtra());

            foreach ($this->helper->getPackageConfig($package) as $group => $files) {
                $files = (array) $files;

                foreach ($files as $file) {
                    $isOptional = false;

                    if (Options::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    if (Options::isVariable($file)) {
                        $this->mergePlan->add($file, $package->getPrettyName(), $group);
                        continue;
                    }

                    $absoluteFilePath = $this->helper->getAbsolutePackageFilePath($package, $options, $file);

                    if (Options::containsWildcard($file)) {
                        $matches = glob($absoluteFilePath);

                        if (empty($matches)) {
                            continue;
                        }

                        foreach ($matches as $match) {
                            $this->mergePlan->add(
                                $this->helper->getRelativePackageFilePath($package, $match),
                                $package->getPrettyName(),
                                $group,
                            );
                        }

                        continue;
                    }

                    if ($isOptional && !is_file($absoluteFilePath)) {
                        continue;
                    }

                    $this->mergePlan->add(
                        $this->helper->getRelativePackageFilePath($package, $absoluteFilePath),
                        $package->getPrettyName(),
                        $group,
                    );
                }
            }
        }
    }

    private function addRootPackageConfigToMergePlan(RootPackageInterface $package, Options $options): void
    {
        foreach ($this->helper->getRootPackageConfig($package, $options) as $group => $files) {
            $this->mergePlan->addMultiple(
                (array) $files,
                Options::ROOT_PACKAGE_NAME,
                $group,
            );
        }
    }

    private function addEnvironmentsConfigsToMergePlan(RootPackageInterface $package, Options $options): void
    {
        foreach ($this->helper->getEnvironmentConfig($package, $options) as $environment => $groups) {
            if ($environment === Options::DEFAULT_ENVIRONMENT) {
                continue;
            }

            if (empty($groups)) {
                $this->mergePlan->addEnvironmentWithoutConfigs($environment);
                continue;
            }

            foreach ($groups as $group => $files) {
                $this->mergePlan->addMultiple(
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
        $mergePlan = $this->mergePlan->toArray();
        ksort($mergePlan);

        $filePath = $this->helper->getPaths()->absolute(Options::MERGE_PLAN_FILENAME);
        $oldContent = is_file($filePath) ? file_get_contents($filePath) : '';

        $content = '<?php'
            . "\n\ndeclare(strict_types=1);"
            . "\n\n// Do not edit. Content will be replaced."
            . "\nreturn " . VarDumper::create($mergePlan)->export(true) . ";\n"
        ;

        if ($this->normalizeLineEndings($oldContent) !== $this->normalizeLineEndings($content)) {
            file_put_contents($filePath, $content);
        }
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }
}
