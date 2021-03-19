<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\Composer;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;

use function array_key_exists;

/**
 * @internal
 */
final class PackagesListBuilder
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return CompletePackage[]
     *
     * @psalm-return array<string, CompletePackage>
     */
    public function build(): array
    {
        $allPackages = $this->getAllPackages();

        $packageDepths = [];
        $this->calcPackageDepths($allPackages, $packageDepths, 0, $this->composer->getPackage(), true);

        $result = [];
        foreach ($this->getSortedPackageNames($packageDepths) as $name) {
            if (array_key_exists($name, $allPackages)) {
                $result[$name] = $allPackages[$name];
            }
        }

        return $result;
    }

    /**
     * Get package names stable sorted by depth
     *
     * @psalm-param array<string, int> $packageDepths
     *
     * @return string[]
     */
    private function getSortedPackageNames(array $packageDepths): array
    {
        $n = 0;
        foreach ($packageDepths as $name => $depth) {
            $packageDepths[$name] = [$depth, ++$n];
        }

        /** @psalm-var array<string, array{0:int,1:int}> $packageDepths */

        uasort($packageDepths, static function (array $a, array $b) {
            $result = $a[0] <=> $b[0];
            return $result === 0 ? $a[1] <=> $b[1] : $result;
        });

        return array_keys($packageDepths);
    }

    /**
     * @param CompletePackage[] $allPackages
     *
     * @psalm-param array<string, CompletePackage> $allPackages
     * @psalm-param array<string, int> $packageDepths
     */
    private function calcPackageDepths(
        array $allPackages,
        array &$packageDepths,
        int $depth,
        PackageInterface $package,
        bool $includingDev
    ): void {
        $name = $package->getPrettyName();

        $packageProcessed = array_key_exists($name, $packageDepths);

        if (!$packageProcessed || $packageDepths[$name] < $depth) {
            $packageDepths[$name] = $depth;
        }

        // Prevent infinite loop in case of circular dependencies
        if ($packageProcessed) {
            return;
        }

        ++$depth;

        $dependencies = $includingDev
            ? array_keys($package->getRequires())
            : [...array_keys($package->getRequires()), ...array_keys($package->getDevRequires())];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency, $allPackages)) {
                $this->calcPackageDepths($allPackages, $packageDepths, $depth, $allPackages[$dependency], false);
            }
        }
    }

    /**
     * @return CompletePackage[]
     *
     * @psalm-return array<string, CompletePackage>
     */
    private function getAllPackages(): array
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $packages = array_filter($packages, static fn ($package) => $package instanceof CompletePackage);

        $result = [];
        foreach ($packages as $package) {
            $result[$package->getPrettyName()] = $package;
        }

        return $result;
    }
}
