<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;

use function array_keys;
use function count;
use function dirname;
use function file_exists;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class ConfigFileHandler
{
    private const BATH_ACTION_CONFIRMATION_MESSAGE = "Apply this action to all the following files? (yes/no)\n > ";
    private const HIDE_CHOICE_CONFIRMATION = 0;
    private const UPDATE_CHOICE_IGNORE = 1;
    private const UPDATE_CHOICE_REPLACE = 2;
    private const UPDATE_CHOICE_COPY_DIST = 3;
    private const CHOICE_SHOW_DIFF = 4;

    private const UPDATE_CHOICES = [
        self::UPDATE_CHOICE_IGNORE => 'Ignore, do nothing.',
        self::UPDATE_CHOICE_REPLACE => 'Replace the local version with the new version.',
        self::UPDATE_CHOICE_COPY_DIST => 'Copy the new version of the file with the ".dist" postfix.',
    ];

    private IOInterface $io;
    private Filesystem $filesystem;
    private ConfigFileDiffer $differ;
    private ComposerConfigProcess $process;
    private ConfigFileUpdater $updater;

    private ?int $updateChoice = null;
    private ?bool $removeChoice = null;
    private bool $confirmedMultipleRemoval = false;
    private bool $displayedOutputTitle = false;

    /**
     * @var string[]
     */
    private array $addedConfigFiles = [];

    /**
     * @var string[]
     */
    private array $copiedConfigFiles = [];

    /**
     * @var string[]
     */
    private array $ignoredConfigFiles = [];

    /**
     * @var string[]
     */
    private array $updatedConfigFiles = [];

    /**
     * @var array<string, string>
     */
    private array $removedPackages = [];

    /**
     * @var array<string, string>
     */
    private array $ignoredRemovedPackages = [];

    /**
     * @param IOInterface $io The IO instance.
     * @param ComposerConfigProcess $process The composer config process instance.
     */
    public function __construct(IOInterface $io, ComposerConfigProcess $process)
    {
        $this->io = $io;
        $this->process = $process;
        $this->filesystem = new Filesystem();
        $this->differ = new ConfigFileDiffer($io, "{$this->process->rootPath()}/{$this->process->configsDirectory()}");
        $this->updater = new ConfigFileUpdater($this->process, $this->differ);
        $this->filesystem->ensureDirectoryExists("{$this->process->rootPath()}/{$this->process->configsDirectory()}");
    }

    /**
     * Updates and removes package configurations.
     *
     * @param string[] $packageRemovals Names of packages to remove.
     */
    public function handle(array $packageRemovals = []): void
    {
        $updateConfigFiles = $this->prepareUpdateConfigFiles();
        $isUpdateMultiple = count($updateConfigFiles) > 1;
        $isRemoveMultiple = count($packageRemovals) > 1;

        foreach ($updateConfigFiles as $configFile) {
            $this->update($configFile, $isUpdateMultiple);
        }

        foreach ($packageRemovals as $packageName) {
            $this->removePackage($packageName, $isRemoveMultiple);
        }

        $this->updater->updateLockFile(array_keys($this->removedPackages));
        $this->updater->updateMergePlan();
        $this->outputMessages();
    }

    /**
     * Adds configuration files if they don't exist and filters configuration files for update.
     *
     * @return ConfigFile[] Configuration files to update.
     */
    private function prepareUpdateConfigFiles(): array
    {
        $updateConfigFiles = [];

        foreach ($this->process->configFiles() as $configFile) {
            if (!file_exists($this->getDestinationPath($configFile->destinationFile()))) {
                $this->addFile($configFile);
                continue;
            }

            if (!$this->updater->needUpdate($configFile) || $this->differ->isConfigFileContentsEqual($configFile)) {
                continue;
            }

            $updateConfigFiles[] = $configFile;
        }

        return $updateConfigFiles;
    }

    private function update(ConfigFile $configFile, bool $isUpdateMultiple): void
    {
        if ($configFile->silentOverride()) {
            $this->updateFile($configFile);
            return;
        }

        if (!$this->io->isInteractive() || !$this->updater->lockFileExisted()) {
            $this->ignoreFile($configFile);
            return;
        }

        $this->interactiveUpdate($configFile, $isUpdateMultiple, true);
    }

    private function interactiveUpdate(ConfigFile $configFile, bool $isUpdateMultiple, bool $withChoiceShowDiff): void
    {
        if ($this->updateChoice !== null && isset(self::UPDATE_CHOICES[$this->updateChoice])) {
            $this->updateChoice($this->updateChoice, $configFile, $isUpdateMultiple);
            return;
        }

        $this->displayOutputTitle();
        $choice = $this->selectUpdate($withChoiceShowDiff, $configFile);

        if ($choice === self::CHOICE_SHOW_DIFF) {
            $this->updateChoice = self::HIDE_CHOICE_CONFIRMATION;
            $this->differ->diff($configFile);
        } elseif ($isUpdateMultiple && $this->updateChoice === null) {
            $this->updateChoice = $this->io->askConfirmation(self::BATH_ACTION_CONFIRMATION_MESSAGE, false)
                ? $choice
                : self::HIDE_CHOICE_CONFIRMATION
            ;
        }

        $this->updateChoice($choice, $configFile, $isUpdateMultiple);
    }

    private function updateChoice(int $choice, ConfigFile $configFile, bool $isUpdateMultiple): void
    {
        if ($choice === self::CHOICE_SHOW_DIFF) {
            $this->interactiveUpdate($configFile, $isUpdateMultiple, false);
            return;
        }

        if ($choice === self::UPDATE_CHOICE_REPLACE) {
            $this->updateFile($configFile);
            return;
        }

        if ($choice === self::UPDATE_CHOICE_COPY_DIST) {
            $this->copyDistFile($configFile);
            return;
        }

        $this->ignoreFile($configFile);
    }

    private function selectUpdate(bool $withChoiceShowDiff, ConfigFile $configFile): int
    {
        if ($withChoiceShowDiff) {
            $question = sprintf(
                "\nThe local version of the \"%s\" config file differs with the new version"
                . " of the file from the vendor.\nSelect one of the following actions:",
                $this->getDestinationWithConfigsDirectory($configFile->destinationFile()),
            );
            $choices = self::UPDATE_CHOICES + [self::CHOICE_SHOW_DIFF => 'Show diff in console.'];
            $errorMessage = 'Value "%s" is invalid. Must be a number: 1, 2, 3 or 4.';
        } else {
            $question = 'Select one of the following actions:';
            $choices = self::UPDATE_CHOICES;
            $errorMessage = 'Value "%s" is invalid. Must be a number: 1, 2, or 3.';
        }

        return (int) $this->io->select($question, $choices, false, false, $errorMessage, false);
    }

    private function addFile(ConfigFile $configFile): void
    {
        $destination = $this->getDestinationPath($configFile->destinationFile());
        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $this->filesystem->copy($configFile->sourceFilePath(), $destination);
        $this->addedConfigFiles[] = $this->getDestinationWithConfigsDirectory($configFile->destinationFile());
    }

    private function copyDistFile(ConfigFile $configFile): void
    {
        $this->filesystem->copy(
            $configFile->sourceFilePath(),
            $this->getDestinationPath($configFile->destinationFile() . '.dist'),
        );
        $this->copiedConfigFiles[] = $this->getDestinationWithConfigsDirectory($configFile->destinationFile());
    }

    private function ignoreFile(ConfigFile $configFile): void
    {
        $this->ignoredConfigFiles[] = $this->getDestinationWithConfigsDirectory($configFile->destinationFile());
    }

    private function updateFile(ConfigFile $configFile): void
    {
        $this->filesystem->copy(
            $configFile->sourceFilePath(),
            $this->getDestinationPath($configFile->destinationFile()),
        );
        $this->updatedConfigFiles[] = $this->getDestinationWithConfigsDirectory($configFile->destinationFile());
    }

    private function removePackage(string $packageName, bool $isRemoveMultiple): void
    {
        if (!file_exists($this->getDestinationPath($packageName))) {
            return;
        }

        if (!$this->io->isInteractive() || !$this->updater->lockFileExisted()) {
            $this->removePackageChoice(false, $packageName);
            return;
        }

        if ($this->removeChoice !== null) {
            $this->removePackageChoice($this->removeChoice, $packageName);
            return;
        }

        $this->displayOutputTitle();

        $choice = $this->io->askConfirmation(
            sprintf(
                "The package was removed from the vendor, remove the \"%s\" configuration? (yes/no)\n > ",
                $this->getDestinationWithConfigsDirectory($packageName),
            ),
            false,
        );

        if ($isRemoveMultiple && $this->confirmedMultipleRemoval === false) {
            $this->removeChoice = $this->io->askConfirmation(self::BATH_ACTION_CONFIRMATION_MESSAGE, false)
                ? $choice
                : null
            ;
            $this->confirmedMultipleRemoval = true;
        }

        $this->removePackageChoice($choice, $packageName);
    }

    private function removePackageChoice(bool $choice, string $packageName): void
    {
        if ($choice === false) {
            $this->ignoredRemovedPackages[$packageName] = $this->getDestinationWithConfigsDirectory($packageName);
            return;
        }

        $this->filesystem->removeDirectory($this->getDestinationPath($packageName));
        $this->removedPackages[$packageName] = $this->getDestinationWithConfigsDirectory($packageName);
    }

    private function outputMessages(): void
    {
        if (!$this->updater->lockFileExisted()) {
            $filename = $this->getDestinationWithConfigsDirectory(Options::DIST_LOCK_FILENAME);
            $this->displayOutputMessages(["The $filename file was generated."]);
            return;
        }

        $messages = [];

        $this->addMessage($messages, $this->addedConfigFiles, 'Config files has been added');
        $this->addMessage($messages, $this->updatedConfigFiles, 'Config files has been updated');
        $this->addMessage(
            $messages,
            $this->copiedConfigFiles,
            'Config files has been copied with the ".dist" postfix',
            'Please review files above and change it according with dist files.',
        );
        $this->addMessage(
            $messages,
            $this->ignoredConfigFiles,
            'Changes in the config files were ignored',
            'Please review the files above and change them yourself if necessary.',
        );

        $this->addMessage($messages, $this->removedPackages, 'Configurations has been removed');
        $this->addMessage(
            $messages,
            $this->ignoredRemovedPackages,
            'The packages were removed from the vendor, but the configurations remained',
            'Please review the files above and remove them yourself if necessary.',
        );

        $this->displayOutputMessages($messages);
    }

    /**
     * @param string[] $messages
     * @param string[] $files
     * @param string $title
     * @param string|null $description
     */
    private function addMessage(array &$messages, array $files, string $title, string $description = null): void
    {
        if (empty($files)) {
            return;
        }

        $messages[] = '';
        $messages[] = $title . ':';

        foreach ($files as $file) {
            $messages[] = ' - ' . $file;
        }

        if ($description !== null) {
            $messages[] = $description;
        }
    }

    /**
     * @param string[] $messages
     */
    private function displayOutputMessages(array $messages): void
    {
        if (!empty($messages)) {
            $this->displayOutputTitle();
            $this->io->write('<bg=magenta;fg=white>' . implode("\n", $messages) . '</>');
        }
    }

    private function displayOutputTitle(): void
    {
        if (!$this->displayedOutputTitle) {
            $this->displayedOutputTitle = true;
            $this->io->write("\n<bg=magenta;fg=white;options=bold>= Yii Config =</>");
        }
    }

    private function getDestinationWithConfigsDirectory(string $destinationFile): string
    {
        return "{$this->process->configsDirectory()}/$destinationFile";
    }

    private function getDestinationPath(string $destinationFile): string
    {
        return "{$this->process->rootPath()}/{$this->process->configsDirectory()}/$destinationFile";
    }
}
