<?php


declare(strict_types=1);


namespace Yiisoft\Config;


use ErrorException;
use Yiisoft\VarDumper\VarDumper;
use function array_key_exists;
use function is_array;
use function is_int;

/**
 * Config takes merge plan prepared by {@see ComposerEventHandler} and executes actual merge for the config group
 * specified.
 */
final class Config
{
    private string $rootPath;
    private string $buildPath;
    private array $config;
    private bool $write;
    private bool $cache;
    private array $build = [];

    /**
     * @param string $rootPath Path to the project root where composer.json is located.
     * @param bool $write Whether to write assembled configs into files.
     * @param bool $cache Whether to use assembled configs from previously written files.
     */
    public function __construct(string $rootPath, bool $write = false, bool $cache = false)
    {
        $this->rootPath = $rootPath;
        $this->buildPath = $rootPath . '/runtime/build/newconfig';
        if ($write && !is_dir($this->buildPath) && !mkdir($this->buildPath, 0777, true) && !is_dir($this->buildPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created.', $this->buildPath));
        }
        $this->config = require $this->rootPath . '/config/packages/merge_plan.php';
        $this->write = $write;
        $this->cache = $cache;
    }

    private function buildGroup(string $group): void
    {
        if (array_key_exists($group, $this->build)) {
            return;
        }

        $this->build[$group] = [];

        $scopeRequire = static function (Config $config): array {
            set_error_handler(static function($errno, $errstr, $errfile, $errline ) {
                throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
            });

            extract(func_get_arg(2), EXTR_SKIP);
            $result = require func_get_arg(1);
            restore_error_handler();
            return $result;
        };

        foreach ($this->config[$group] as $name => $files) {
            $files = (array)$files;

            $configsPath = $this->getConfigsPath($name);

            foreach ($files as $file) {
                if ($this->isVariable($file)) {
                    $variableName = substr($file, 1);
                    $this->buildGroup($variableName);
                    $this->build[$group] = $this->merge([$file, $group, $name], '', $this->build[$group], $this->build[$variableName]);
                    continue;
                }

                $isOptional = $this->isOptional($file);
                if ($isOptional) {
                    $file = substr($file, 1);
                }

                $path = $configsPath . '/' . $file;

                if ($this->containsWildcard($file)) {
                    $matches = glob($path);

                    foreach ($matches as $match) {
                        $scope = [];
                        if ($group !== 'params') {
                            $scope['params'] = $this->build['params'];
                        }
                        $config = $scopeRequire($this, $match, $scope);
                        $this->build[$group] = $this->merge([$file, $group, $name], '', $this->build[$group], $config);
                    }
                    continue;
                }

                if ($isOptional && !file_exists($path)) {
                    continue;
                }

                $scope = [];
                if ($group !== 'params') {
                    $scope['params'] = $this->build['params'];
                }

                $config = $scopeRequire($this, $path, $scope);
                $this->build[$group] = $this->merge([$file, $group, $name], '', $this->build[$group], $config);
            }
        }

        if ($this->write) {
            // This is debug only. Export isn't working correctly (not exporting namespaces)
            $filePath = $this->buildPath . '/' . $group . '.php';
            file_put_contents($filePath, "<?php\n\ndeclare(strict_types=1);\n\nreturn " . VarDumper::create($this->build[$group])->export(true) . ";\n");
        }
    }

    public function get(string $name): array
    {
        $this->buildGroup('params');
        $this->buildGroup($name);
        return $this->build[$name];
    }

    private function containsWildcard(string $file): bool
    {
        return strpos($file, '*') !== false;
    }

    private function isOptional(string $file): bool
    {
        return strpos($file, '?') === 0;
    }

    private function isVariable(string $file): bool
    {
        return strpos($file, '$') === 0;
    }

    private function merge(array $context, $path = '', array ...$args): array
    {
        $result = array_shift($args) ?: [];
        while (!empty($args)) {
            /** @psalm-var mixed $v */
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $result) && $result[$k] !== $v) {
                        /** @var mixed */
                        $result[] = $v;
                    } else {
                        /** @var mixed */
                        $result[$k] = $v;
                    }
                } elseif (is_array($v) && isset($result[$k]) && is_array($result[$k])) {
                    $result[$k] = $this->merge($context, $path ? $path . ' => ' . $k : $k, $result[$k], $v);
                } else {
                    if (array_key_exists($k, $result)) {
                        throw new ErrorException($this->getErrorMessage($k, $path, $result[$k], $context), 0, E_USER_ERROR);
                    }

                    /** @var mixed */
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    private function getErrorMessage(string $key, string $path, $value, array $context): string
    {
        [$file, $group, $packageName] = $context;

        $config = $this->config[$group];
        unset($config[$packageName]);

        $suggestedConfigPaths = [];
        foreach ($config as $package => $packageConfigs) {
            foreach ($packageConfigs as $packageConfig) {
                if ($this->isVariable($packageConfig)) {
                    continue;
                }

                if ($this->isOptional($packageConfig)) {
                    $packageConfig = substr($packageConfig, 1);
                }

                $fullConfigPath = $this->getConfigsPath($package) . '/' . $packageConfig;

                if (file_exists($fullConfigPath)) {
                    $configContents = file_get_contents($fullConfigPath);
                    if (strpos($configContents, $key) !== false) {
                        $suggestedConfigPaths[] = $fullConfigPath;
                    }
                }
            }
        }

        return sprintf(
            'Duplicate key "%s" in "%s". Configs with the same key: "%s".',
            $path ? $path . ' => ' . $key : $key,
            $this->getConfigsPath($packageName) . '/' . $file,
            implode('", "', $suggestedConfigPaths)
        );
    }

    /**
     * Get path to package configs.
     *
     * @param string $packageName Name of the package. "/" stands for application package.
     * @return string Path to package configs.
     */
    private function getConfigsPath(string $packageName): string
    {
        if ($packageName === '/') {
            $configsPath = $this->rootPath;
        } else {
            $configsPath = $this->rootPath . '/config/packages/' . $packageName;
        }
        return $configsPath;
    }
}
