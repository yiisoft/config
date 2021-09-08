<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Store the configuration paths necessary for using the {@see Config} instance.
 */
final class ConfigPaths
{
    private string $configsPath;
    private string $vendorPath;
    private string $relativeConfigsPath;
    private string $relativeVendorPath;

    /**
     * @param string $rootPath The absolute path to the project root where `composer.json` is located.
     * @param string|null $configsDirectory The relative path to the configuration storage location.
     * @param string|null $vendorDirectory The relative path to the vendor directory.
     */
    public function __construct(string $rootPath, string $configsDirectory = null, string $vendorDirectory = null)
    {
        $this->relativeConfigsPath = trim($configsDirectory ?? Options::DEFAULT_CONFIGS_DIRECTORY, '/');
        $this->relativeVendorPath = trim($vendorDirectory ?? Options::DEFAULT_VENDOR_DIRECTORY, '/');
        $this->configsPath = $rootPath . '/' . $this->relativeConfigsPath;
        $this->vendorPath = $rootPath . '/' . $this->relativeVendorPath;
    }

    /**
     * Returns the absolute path to the configuration file.
     *
     * @param string $file Config file.
     * @param string $package Name of the package. {@see Options::ROOT_PACKAGE_NAME} stands for the root package.
     *
     * @return string The absolute path to the configuration file.
     */
    public function absolute(string $file, string $package = Options::ROOT_PACKAGE_NAME): string
    {
        if ($package === Options::ROOT_PACKAGE_NAME) {
            return "$this->configsPath/$file";
        }

        return "$this->vendorPath/$package/$file";
    }

    /**
     * Returns the relative path to the configuration file.
     *
     * @param string $file Config file.
     * @param string $package Name of the package. {@see Options::ROOT_PACKAGE_NAME} stands for the root package.
     *
     * @return string The relative path to the configuration file.
     */
    public function relative(string $file, string $package = Options::ROOT_PACKAGE_NAME): string
    {
        if ($package === Options::ROOT_PACKAGE_NAME) {
            if (strpos($file,"$this->configsPath/") === 0) {
                $file = substr($file, strlen("$this->configsPath/"));
            }

            return "$this->relativeConfigsPath/$file";
        }

        if (strpos($file,"$this->vendorPath/") === 0) {
            return $this->relativeVendorPath . substr($file, strlen("$this->vendorPath"));
        }

        return "$this->relativeVendorPath/$package/$file";
    }
}
