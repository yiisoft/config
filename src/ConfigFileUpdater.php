<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Yiisoft\VarDumper\VarDumper;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function json_decode;
use function json_encode;
use function ksort;
use function md5;

/**
 * @internal
 */
final class ConfigFileUpdater
{
    private ComposerConfigProcess $process;
    private ConfigFileDiffer $differ;
    private string $lockFilePath;
    private bool $lockFileExisted = false;

    /**
     * @var array<string, array<string, string>>
     */
    private array $lockFileData = [];

    /**
     * @var array<string, string>
     */
    private array $newFileHashes = [];

    public function __construct(ComposerConfigProcess $process, ConfigFileDiffer $differ)
    {
        $this->process = $process;
        $this->differ = $differ;
        $this->lockFilePath = "{$process->rootPath()}/{$process->configsDirectory()}/" . Options::DIST_LOCK_FILENAME;

        if (is_file($this->lockFilePath)) {
            $this->lockFileExisted = true;
            /** @psalm-suppress MixedAssignment*/
            $this->lockFileData = json_decode(file_get_contents($this->lockFilePath), true, 512,  JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @param string[] $removedPackages
     */
    public function updateLockFile(array $removedPackages = []): void
    {
        $lockFileChanged = false;

        foreach ($this->process->configFiles() as $configFile) {
            if (!$this->needUpdate($configFile)) {
                continue;
            }

            $package = $configFile->package()->getPrettyName();
            $version = $configFile->package()->getFullPrettyVersion();

            if (!isset($this->lockFileData[$package]['version']) || $this->lockFileData[$package]['version'] !== $version) {
                $this->lockFileData[$package]['version'] = $version;
            }

            $this->lockFileData[$package][$configFile->filename()] = $this->hash($configFile);
            $lockFileChanged = true;
        }

        foreach ($removedPackages as $removedPackage) {
            if (isset($this->lockFileData[$removedPackage])) {
                unset($this->lockFileData[$removedPackage]);
                $lockFileChanged = true;
            }
        }

        if ($lockFileChanged) {
            ksort($this->lockFileData);

            file_put_contents($this->lockFilePath, json_encode(
                $this->lockFileData,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        }
    }

    public function updateMergePlan(): void
    {
        $mergePlan = $this->process->mergePlan();
        ksort($mergePlan);

        $filePath = "{$this->process->rootPath()}/{$this->process->configsDirectory()}/" . Options::MERGE_PLAN_FILENAME;
        $oldContent = is_file($filePath) ? file_get_contents($filePath) : '';

        $content = '<?php'
            . "\n\ndeclare(strict_types=1);"
            . "\n\n// Do not edit. Content will be replaced."
            . "\nreturn " . VarDumper::create($mergePlan)->export(true) . ";\n"
        ;

        if (!$this->differ->isContentEqual($oldContent, $content)) {
            file_put_contents($filePath, $content);
        }
    }

    public function needUpdate(ConfigFile $configFile): bool
    {
        if (!$this->lockFileExisted || !isset($this->lockFileData[$configFile->package()->getPrettyName()])) {
            return true;
        }

        $lockData = $this->lockFileData[$configFile->package()->getPrettyName()];
        $version = $configFile->package()->getFullPrettyVersion();
        $filename = $configFile->filename();

        if (isset($lockData['version']) && $lockData['version'] === $version) {
            $this->lockFileData[$configFile->package()->getPrettyName()][$filename] ??= $this->hash($configFile);
            return false;
        }

        if (isset($lockData[$filename]) && $lockData[$filename] === $this->hash($configFile)) {
            return false;
        }

        return true;
    }

    public function lockFileExisted(): bool
    {
        return $this->lockFileExisted;
    }

    private function hash(ConfigFile $configFile): string
    {
        if (isset($this->newFileHashes[$configFile->destinationFile()])) {
            return $this->newFileHashes[$configFile->destinationFile()];
        }

        $hash = md5(json_encode(file_get_contents($configFile->sourceFilePath())));
        $this->newFileHashes[$configFile->destinationFile()] = $hash;

        return $hash;
    }
}
