<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

/**
 * @internal
 */
final class FilesExctractor
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private string $environment;

    /**
     * @psalm-var array<string,array<string,Context>>
     */
    private array $cache = [];

    public function __construct(
        ConfigPaths $paths,
        MergePlan $mergePlan,
        string $environment
    ) {
        $this->paths = $paths;
        $this->mergePlan = $mergePlan;
        $this->environment = $environment;
    }

    /**
     * @psalm-return array<string,Context>
     */
    public function extract(string $group): array
    {
        if (isset($this->cache[$group])) {
            return $this->cache[$group];
        }

        $this->performExtract($group);

        return $this->cache[$group];
    }

    public function performExtract(string $group): void
    {
        if (isset($this->cache[$group])) {
            return;
        }

        $environment = $this->prepareEnvironment($group);

        $this->cache[$group] = [];

        $this->process(
            Options::ROOT_PACKAGE_NAME,
            $group,
            $this->mergePlan->getGroup($group, Options::ROOT_PACKAGE_NAME)
        );

        if ($environment !== Options::ROOT_PACKAGE_NAME) {
            $this->process(
                $environment,
                $group,
                $this->mergePlan->getGroup($group, $environment)
            );
        }
    }

    /**
     * @psalm-param array<string, string[]> $data
     */
    private function process(string $environment, string $group, array $data): void
    {
        foreach ($data as $package => $items) {
            foreach ($items as $item) {
                if (Options::isVariable($item)) {
                    $this->cache[$group][$item] = new Context($environment, $group, $package, $item, true);
                    continue;
                }

                $isOptional = Options::isOptional($item);
                if ($isOptional) {
                    $item = substr($item, 1);
                }

                $filePath = $this->paths->absolute($item, $package);

                $files = Options::containsWildcard($item) ? glob($filePath) : [$filePath];
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $this->cache[$group][$file] = new Context($environment, $group, $package, $file, false);
                    } elseif (!$isOptional) {
                        $this->throwException(sprintf('The "%s" file does not found.', $file));
                    }
                }
            }
        }
    }

    /**
     * Checks the group name and returns actual environment name.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If the group does not exist.
     *
     * @return string The actual environment name.
     */
    private function prepareEnvironment(string $group): string
    {
        if (!$this->mergePlan->hasGroup($group, $this->environment)) {
            if (
                $this->environment === Options::DEFAULT_ENVIRONMENT ||
                !$this->mergePlan->hasGroup($group, Options::DEFAULT_ENVIRONMENT)
            ) {
                $this->throwException(sprintf('The "%s" configuration group does not exist.', $group));
            }

            return Options::DEFAULT_ENVIRONMENT;
        }

        return $this->environment;
    }

    /**
     * @param string $message
     *
     * @throws ErrorException
     */
    private function throwException(string $message): void
    {
        throw new ErrorException($message, 0, E_USER_ERROR);
    }
}
