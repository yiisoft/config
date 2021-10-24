<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use function array_merge;
use function glob;
use function is_file;
use function sprintf;
use function substr;

/**
 * @internal
 */
final class FilesExtractor
{
    private ConfigPaths $paths;
    private MergePlan $mergePlan;
    private string $environment;

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
     * Extracts group configuration data from files.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If an error occurred during the extract.
     *
     * @psalm-return array<string, Context>
     */
    public function extract(string $group): array
    {
        $environment = $this->prepareEnvironment($group);

        $result = $this->process(Options::DEFAULT_ENVIRONMENT, $group, $this->mergePlan->getGroup($group));

        if ($environment !== Options::DEFAULT_ENVIRONMENT) {
            $result = array_merge(
                $result,
                $this->process(
                    $environment,
                    $group,
                    $this->mergePlan->getGroup($group, $environment)
                )
            );
        }

        return $result;
    }

    /**
     * @psalm-param array<string, string[]> $data
     *
     * @throws ErrorException If an error occurred during the process.
     *
     * @psalm-return array<string, Context>
     */
    private function process(string $environment, string $group, array $data): array
    {
        $result = [];

        foreach ($data as $package => $items) {
            foreach ($items as $item) {
                if (Options::isVariable($item)) {
                    $result[$item] = new Context($environment, $group, $package, $item, true);
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
                        $result[$file] = new Context($environment, $group, $package, $file, false);
                    } elseif (!$isOptional) {
                        $this->throwException(sprintf('The "%s" file does not found.', $file));
                    }
                }
            }
        }

        return $result;
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
