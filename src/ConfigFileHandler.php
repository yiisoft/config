<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Yiisoft\VarDumper\VarDumper;

use function count;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function ksort;
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

    private const UPDATE_CHOICES = [
        self::UPDATE_CHOICE_IGNORE => 'Ignore, do nothing.',
        self::UPDATE_CHOICE_REPLACE => 'Replace the local version with the new version.',
        self::UPDATE_CHOICE_COPY_DIST => 'Copy the new version of the file with the ".dist" postfix.',
    ];

    private IOInterface $io;
    private Filesystem $filesystem;
    private string $rootPath;
    private string $configsDirectory;
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
     * @var string[]
     */
    private array $removedPackages = [];

    /**
     * @var string[]
     */
    private array $ignoredRemovedPackages = [];

    /**
     * @param IOInterface $io The IO instance.
     * @param string $rootPath The full path to directory containing composer.json.
     * @param string $configsDirectory The name of the directory containing the configuration files.
     */
    public function __construct(IOInterface $io, string $rootPath, string $configsDirectory)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem();
        $this->rootPath = $rootPath;
        $this->configsDirectory = $configsDirectory;
        $this->filesystem->ensureDirectoryExists($this->rootPath . '/' . $this->configsDirectory);
    }

    /**
     * Handles config files after running the `composer create-project` command.
     *
     * @param ConfigFile[] $configFiles Configuration files to change.
     * @param array $mergePlan Data for changing the merge plan.
     */
    public function handleAfterCreateProject(array $configFiles, array $mergePlan): void
    {
        foreach ($configFiles as $configFile) {
            if (!$this->destinationConfigFileExist($configFile)) {
                $this->addFile($configFile);
                continue;
            }

            if (!$this->equalsConfigFileContents($configFile)) {
                $this->ignoreFile($configFile);
            }
        }

        $this->updateMergePlan($mergePlan);
        $this->outputMessagesAfterCreateProject();
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
        if (!$this->destinationConfigFileExist($configFile)) {
            $this->addFile($configFile);
            return;
        }

        if ($this->equalsConfigFileContents($configFile)) {
            return;
        }

        if ($configFile->silentOverride()) {
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

        $this->displayOutputTitle();

        $choice = (int) $this->io->select(
            sprintf(
                "\nThe local version of the \"%s\" config file differs with the new version"
                . " of the file from the vendor.\nSelect one of the following actions:",
                $this->getDestinationWithConfigsDirectory($configFile->destinationFile()),
            ),
            self::UPDATE_CHOICES,
            false,
            false,
            'Value "%s" is invalid. Must be a number: 1, 2, or 3.',
            false,
        );

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

        if (!$this->io->isInteractive()) {
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
            $this->ignoredRemovedPackages[] = $this->getDestinationWithConfigsDirectory($packageName);
            return;
        }

        $this->filesystem->removeDirectory($this->getDestinationPath($packageName));
        $this->removedPackages[] = $this->getDestinationWithConfigsDirectory($packageName);
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

    private function outputMessagesAfterCreateProject(): void
    {
        $messages = [];

        $this->addMessage(
            $messages,
            $this->ignoredConfigFiles,
            'Config files were changed to run the application template',
            sprintf(
                'You can change any configuration files located in the "%s" for yourself.',
                $this->configsDirectory,
            ),
        );

        $this->displayOutputMessages($messages);
    }

    private function outputMessages(): void
    {
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
        return $this->configsDirectory . '/' . $destinationFile;
    }

    private function getDestinationPath(string $destinationFile): string
    {
        return $this->rootPath . '/' . $this->configsDirectory . '/' . $destinationFile;
    }

    private function destinationConfigFileExist(ConfigFile $configFile): bool
    {
        return file_exists($this->getDestinationPath($configFile->destinationFile()));
    }

    private function equalsConfigFileContents(ConfigFile $configFile): bool
    {
        return $this->equalsIgnoringLineEndings(
            file_get_contents($configFile->sourceFilePath()),
            file_get_contents($this->getDestinationPath($configFile->destinationFile())),
        );
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
}
