<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yiisoft\VarDumper\VarDumper;

use function count;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function ksort;
use function rtrim;
use function sprintf;
use function trim;

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

    private const UPDATE_CHOICES = [
        self::UPDATE_CHOICE_IGNORE => 'Ignore, do nothing (default).',
        self::UPDATE_CHOICE_REPLACE => 'Replace the local version with the new version.',
        self::UPDATE_CHOICE_COPY_DIST => 'Copy the new version of the file with the ".dist" postfix.',
    ];

    private IOInterface $io;
    private Filesystem $filesystem;
    private string $rootPath;
    private string $configsPath;
    private ?int $updateChoice = null;
    private ?bool $removeChoice = null;
    private bool $confirmedMultipleRemoval = false;

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
     * @var string[]
     */
    private array $removedPackages = [];

    /**
     * @var string[]
     */
    private array $ignoredRemovedPackages = [];

    /**
     * @param IOInterface $io The IO instance.
     * @param string $rootPath Path to directory containing composer.json.
     * @param string $configsPath Path to directory containing configuration files.
     */
    public function __construct(IOInterface $io, string $rootPath, string $configsPath = Options::DEFAULT_CONFIGS_PATH)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem();
        $this->rootPath = rtrim($rootPath, '/');
        $this->configsPath = trim($configsPath, '/');
        $this->filesystem->ensureDirectoryExists($this->rootPath . '/' . $this->configsPath);
    }

    /**
     * Updates and removes package configurations.
     *
     * @param ConfigFile[] $configFiles Configuration files to change.
     * @param string[] $removedPackages Names of removed packages.
     * @param array $mergePlan Data for changing the merge plan.
     */
    public function handle(array $configFiles, array $removedPackages, array $mergePlan): void
    {
        $isUpdateMultiple = count($configFiles) > 1;
        $isRemoveMultiple = count($removedPackages) > 1;

        foreach ($configFiles as $configFile) {
            $this->update($configFile, $isUpdateMultiple);
        }

        foreach ($removedPackages as $packageName) {
            $this->removePackage($packageName, $isRemoveMultiple);
        }

        $this->updateMergePlan($mergePlan);
        $this->outputMessages();
    }

    private function update(ConfigFile $configFile, bool $isUpdateMultiple): void
    {
        $sourceFilePath = $configFile->getSourceFilePath();
        $destination = $this->getDestinationPath($configFile->getDestinationFile());

        if (!file_exists($destination)) {
            $this->addFile($configFile);
            return;
        }

        if ($this->equalsIgnoringLineEndings(file_get_contents($sourceFilePath), file_get_contents($destination))) {
            return;
        }

        if ($configFile->isSilentOverride()) {
            $this->updateFile($configFile);
            return;
        }

        if (!$this->io->isInteractive()) {
            $this->ignoreFile($configFile);
            return;
        }

        $this->interactiveUpdate($configFile, $isUpdateMultiple);
    }

    private function interactiveUpdate(ConfigFile $configFile, bool $isUpdateMultiple): void
    {
        if ($this->updateChoice !== null && isset(self::UPDATE_CHOICES[$this->updateChoice])) {
            $this->updateChoice($this->updateChoice, $configFile);
            return;
        }

        $choice = (int) $this->io->select(
            sprintf(
                "\nThe local version of the \"%s\" config file differs with the new version"
                . " of the file from the vendor.\nSelect one of the following actions:",
                $this->getDestinationWithConfigsPath($configFile->getDestinationFile()),
            ),
            self::UPDATE_CHOICES,
            (string) self::UPDATE_CHOICE_IGNORE,
            false,
            'Value "%s" is invalid. Must be a number: 1, 2, or 3.',
            false,
        );

        if (!isset(self::UPDATE_CHOICES[$choice])) {
            $choice = self::UPDATE_CHOICE_IGNORE;
        }

        if ($isUpdateMultiple && $this->updateChoice === null) {
            $this->updateChoice = $this->io->askConfirmation(self::BATH_ACTION_CONFIRMATION_MESSAGE, false)
                ? $choice
                : self::HIDE_CHOICE_CONFIRMATION
            ;
        }

        $this->updateChoice($choice, $configFile);
    }

    private function updateChoice(int $choice, ConfigFile $configFile): void
    {
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

    private function addFile(ConfigFile $configFile): void
    {
        $destination = $this->getDestinationPath($configFile->getDestinationFile());
        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $this->filesystem->copy($configFile->getSourceFilePath(), $destination);
        $this->addedConfigFiles[] = $this->getDestinationWithConfigsPath($configFile->getDestinationFile());
    }

    private function copyDistFile(ConfigFile $configFile): void
    {
        $this->filesystem->copy(
            $configFile->getSourceFilePath(),
            $this->getDestinationPath($configFile->getDestinationFile() . '.dist'),
        );
        $this->copiedConfigFiles[] = $this->getDestinationWithConfigsPath($configFile->getDestinationFile());
    }

    private function ignoreFile(ConfigFile $configFile): void
    {
        $this->ignoredConfigFiles[] = $this->getDestinationWithConfigsPath($configFile->getDestinationFile());
    }

    private function updateFile(ConfigFile $configFile): void
    {
        $this->filesystem->copy(
            $configFile->getSourceFilePath(),
            $this->getDestinationPath($configFile->getDestinationFile()),
        );
        $this->updatedConfigFiles[] = $this->getDestinationWithConfigsPath($configFile->getDestinationFile());
    }

    private function removePackage(string $packageName, bool $isRemoveMultiple): void
    {
        if (!file_exists($this->getDestinationPath($packageName))) {
            return;
        }

        if (!$this->io->isInteractive()) {
            $this->removePackageChoice(false, $packageName);
            return;
        }

        if ($this->removeChoice !== null) {
            $this->removePackageChoice($this->removeChoice, $packageName);
            return;
        }

        $choice = $this->io->askConfirmation(
            sprintf(
                "The package was removed from the vendor, remove the \"%s\" configuration? (yes/no)\n > ",
                $this->getDestinationWithConfigsPath($packageName),
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
            $this->ignoredRemovedPackages[] = $this->getDestinationWithConfigsPath($packageName);
            return;
        }

        $this->filesystem->removeDirectory($this->getDestinationPath($packageName));
        $this->removedPackages[] = $this->getDestinationWithConfigsPath($packageName);
    }

    private function updateMergePlan(array $mergePlan): void
    {
        // Sort groups by alphabetical
        ksort($mergePlan);

        $filePath = $this->getDestinationPath(Options::MERGE_PLAN_FILENAME);
        $oldContent = file_exists($filePath) ? file_get_contents($filePath) : '';

        $content = '<?php' .
            "\n\n" .
            'declare(strict_types=1);' .
            "\n\n" .
            '// Do not edit. Content will be replaced.' .
            "\n" .
            'return ' . VarDumper::create($mergePlan)->export(true) . ';' .
            "\n"
        ;

        if (!$this->equalsIgnoringLineEndings($oldContent, $content)) {
            file_put_contents($filePath, $content);
        }
    }

    private function outputMessages(): void
    {
        $messages = [];

        if ($this->addedConfigFiles) {
            $this->addMessage($messages, $this->addedConfigFiles, 'Config files has been added');
        }

        if ($this->updatedConfigFiles) {
            $this->addMessage($messages, $this->updatedConfigFiles, 'Config files has been updated');
        }

        if ($this->copiedConfigFiles) {
            $this->addMessage(
                $messages,
                $this->copiedConfigFiles,
                'Config files has been copied with the ".dist" postfix',
                'Please review files above and change it according with dist files.',
            );
        }

        if ($this->ignoredConfigFiles) {
            $this->addMessage(
                $messages,
                $this->ignoredConfigFiles,
                'Changes in the config files were ignored',
                'Please review the files above and change them yourself if necessary.',
            );
        }

        if ($this->removedPackages) {
            $this->addMessage($messages, $this->removedPackages, 'Configurations has been removed');
        }

        if ($this->ignoredRemovedPackages) {
            $this->addMessage(
                $messages,
                $this->ignoredRemovedPackages,
                'The packages were removed from the vendor, but the configurations remained',
                'Please review the files above and remove them yourself if necessary.',
            );
        }

        if ($messages) {
            (new ConsoleOutput())->writeln('<bg=magenta;fg=white>' . implode("\n", $messages) . '</>');
        }
    }

    /**
     * @param string[] $messages
     * @param string[] $files
     * @param string $title
     * @param string|null $description
     */
    private function addMessage(array &$messages, array $files, string $title, string $description = null): void
    {
        $messages[] = '';
        $messages[] = $title . ':';

        foreach ($files as $file) {
            $messages[] = ' - ' . $file;
        }

        if ($description !== null) {
            $messages[] = $description;
        }
    }

    private function equalsIgnoringLineEndings(string $a, string $b): bool
    {
        return $this->normalizeLineEndings($a) === $this->normalizeLineEndings($b);
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }

    private function getDestinationWithConfigsPath(string $destinationFile): string
    {
        return $this->configsPath . '/' . $destinationFile;
    }

    private function getDestinationPath(string $destinationFile): string
    {
        return $this->rootPath . '/' . $this->configsPath . '/' . $destinationFile;
    }
}
