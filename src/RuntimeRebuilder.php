<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function is_array;
use function is_file;
use function json_decode;
use function shell_exec;
use function sprintf;

/**
 * Handles automatic rebuild of merge plan at runtime when auto-rebuild option is enabled.
 *
 * @internal
 */
final class RuntimeRebuilder
{
    private string $rootPath;

    public function __construct(
        private readonly ConfigPaths $paths,
        private readonly string $mergePlanFile,
    ) {
        // Extract root path from ConfigPaths
        // The merge plan file is relative to config directory, but composer.json is in root
        $mergePlanPath = $this->paths->absolute($mergePlanFile);
        $this->rootPath = dirname($mergePlanPath);
    }

    /**
     * Checks if merge plan needs rebuilding and rebuilds it if necessary.
     *
     * This method:
     * 1. Checks if auto-rebuild is enabled in composer.json
     * 2. Checks if merge plan file exists and is up-to-date
     * 3. Triggers rebuild via shell command if needed
     */
    public function ensureMergePlanIsUpToDate(): void
    {
        if (!$this->isAutoRebuildEnabled()) {
            return;
        }

        if ($this->isMergePlanUpToDate()) {
            return;
        }

        $this->rebuild();
    }

    private function isAutoRebuildEnabled(): bool
    {
        $composerJsonPath = $this->rootPath . '/composer.json';

        if (!is_file($composerJsonPath)) {
            return false;
        }

        $content = file_get_contents($composerJsonPath);

        if ($content === false) {
            return false;
        }

        $composerJson = json_decode($content, true);

        if (!is_array($composerJson)) {
            return false;
        }

        return ($composerJson['extra']['config-plugin-options']['auto-rebuild'] ?? false) === true;
    }

    private function isMergePlanUpToDate(): bool
    {
        $mergePlanPath = $this->paths->absolute($this->mergePlanFile);

        if (!file_exists($mergePlanPath)) {
            return false;
        }

        $mergePlanTime = filemtime($mergePlanPath);

        if ($mergePlanTime === false) {
            return false;
        }

        $composerJsonPath = $this->rootPath . '/composer.json';

        if (file_exists($composerJsonPath)) {
            $composerJsonTime = filemtime($composerJsonPath);
            if ($composerJsonTime !== false && $composerJsonTime > $mergePlanTime) {
                return false;
            }
        }

        $composerLockPath = $this->rootPath . '/composer.lock';

        if (file_exists($composerLockPath)) {
            $composerLockTime = filemtime($composerLockPath);
            if ($composerLockTime !== false && $composerLockTime > $mergePlanTime) {
                return false;
            }
        }

        return true;
    }

    private function rebuild(): void
    {
        $composerBin = $this->findComposerBinary();

        // Execute the rebuild command
        $command = sprintf(
            'cd %s && %s yii-config-rebuild 2>&1',
            escapeshellarg($this->rootPath),
            escapeshellarg($composerBin),
        );

        exec($command, $output, $exitCode);

        // If rebuild fails, silently continue - the error will be caught when loading the merge plan
    }

    private function findComposerBinary(): string
    {
        // Try common locations for composer
        $possiblePaths = [
            'composer',
            'composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($possiblePaths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }

        return 'composer'; // Fallback to 'composer' in PATH
    }

    private function commandExists(string $command): bool
    {
        $result = shell_exec(sprintf('which %s 2>/dev/null', escapeshellarg($command)));
        return !empty($result);
    }
}
