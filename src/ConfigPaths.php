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
    private string $rootPath;
    private string $configPath;
    private string $vendorPath;

    /**
     * @param string $rootPath The absolute path to the project root where `composer.json` is located.
     * @param string|null $configDirectory The relative path to the configuration storage location.
     * @param string|null $vendorDirectory The relative path to the vendor directory.
     */
    public function __construct(string $rootPath, string $configDirectory = null, string $vendorDirectory = null)
    {
        $this->rootPath = $rootPath;

        $configDirectory = trim($configDirectory ?? Options::DEFAULT_CONFIG_DIRECTORY, '/');
        $this->configPath = $rootPath . ($configDirectory === '' ? '' : "/$configDirectory");

        $vendorDirectory = trim($vendorDirectory ?? Options::DEFAULT_VENDOR_DIRECTORY, '/');
        $this->vendorPath = $rootPath . ($vendorDirectory === '' ? '' : "/$vendorDirectory");
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
            return "$this->configPath/$file";
        }

        if ($package === Options::VENDOR_OVERRIDE_PACKAGE_NAME) {
            return "$this->vendorPath/$file";
        }

        return "$this->vendorPath/$package/$file";
    }

    /**
     * Returns the relative path to the configuration file.
     *
     * @param string $file Config file.
     *
     * @return string The relative path to the configuration file.
     */
    public function relative(string $file): string
    {
        return strpos($file, "$this->rootPath/") === 0
            ? substr($file, strlen("$this->rootPath/"))
            : $file;
    }
}
