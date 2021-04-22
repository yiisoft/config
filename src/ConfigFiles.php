<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yiisoft\VarDumper\VarDumper;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function ksort;
use function strpos;
use function trim;

/**
 * @internal
 */
final class ConfigFiles
{
    private const HIDE_CHOICE_CONFIRMATION = 0;
    private const UPDATE_CHOICE_IGNORE = 1;
    private const UPDATE_CHOICE_REPLACE = 2;
    private const UPDATE_CHOICE_COPY = 3;

    private const UPDATE_CHOICES = [
        self::UPDATE_CHOICE_IGNORE => 'Ignore, do nothing (default).',
        self::UPDATE_CHOICE_REPLACE => 'Replace the local version with the new version.',
        self::UPDATE_CHOICE_COPY => 'Copy the new version of the file with the ".dist" postfix.',
    ];

    private IOInterface $io;
    private Filesystem $filesystem;
    private string $rootPath;
    private string $configsPath;
    private ?int $choice = null;

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

    public function __construct(IOInterface $io, string $rootPath, string $configsPath = Options::DEFAULT_CONFIGS_PATH)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem();
        $this->rootPath = trim($rootPath, '/');
        $this->configsPath = trim($configsPath, '/');
    }

    public static function containsWildcard(string $file): bool
    {
        return strpos($file, '*') !== false;
    }

    public static function isOptional(string $file): bool
    {
        return strpos($file, '?') === 0;
    }

    public static function isVariable(string $file): bool
    {
        return strpos($file, '$') === 0;
    }

    public function updateMergePlaneAndOutputResult(array $mergePlan): void
    {
        // Sort groups by alphabetical
        ksort($mergePlan);

        $this->updateMergePlan($mergePlan);
        $this->outputMessages();
    }

    public function updateConfiguration(string $sourceFile, string $destinationFile, bool $silentOverride = false): void
    {
        $destination = $this->getDestinationPath($destinationFile);

        if (!file_exists($destination)) {
            $this->add($sourceFile, $destinationFile);
            return;
        }

        if ($this->equalsIgnoringLineEndings(file_get_contents($sourceFile), file_get_contents($destination))) {
            return;
        }

        if ($silentOverride) {
            $this->update($sourceFile, $destinationFile);
            return;
        }

        if (!$this->io->isInteractive()) {
            $this->dist($sourceFile, $destinationFile);
            return;
        }

        $this->interactiveUpdateConfiguration($sourceFile, $destinationFile);
    }

    private function interactiveUpdateConfiguration(string $sourceFile, string $destinationFile): void
    {
        if ($this->choice !== null && isset(self::UPDATE_CHOICES[$this->choice])) {
            $this->updateChoice($this->choice, $sourceFile, $destinationFile);
            return;
        }

        $choice = (int) $this->io->select(
            sprintf(
                "\nThe local version of the \"%s\" file differs with the new version \"%s\"."
                . "\nSelect one of the following actions:",
                $this->getDestinationPath($destinationFile),
                $sourceFile,
            ),
            self::UPDATE_CHOICES,
            (string) self::UPDATE_CHOICE_IGNORE,
        );

        if (!isset(self::UPDATE_CHOICES[$choice])) {
            $choice = self::UPDATE_CHOICE_IGNORE;
        }

        if ($this->choice === null) {
            $this->choice = $this->io->askConfirmation("Apply this action to all the following files? (yes/no)\n > ")
                ? $choice
                : self::HIDE_CHOICE_CONFIRMATION
            ;
        }

        $this->updateChoice($choice, $sourceFile, $destinationFile);
    }

    private function updateChoice(int $choice, string $sourceFile, string $destinationFile): void
    {
        if ($choice === self::UPDATE_CHOICE_REPLACE) {
            $this->update($sourceFile, $destinationFile);
            return;
        }

        if ($choice === self::UPDATE_CHOICE_COPY) {
            $this->dist($sourceFile, $destinationFile);
            return;
        }

        $this->ignoredConfigFiles[] = $this->getDestinationFileWithConfigsPath($destinationFile);
    }

    private function add(string $sourceFile, string $destinationFile): void
    {
        $destination = $this->getDestinationPath($destinationFile);
        $this->filesystem->ensureDirectoryExists(dirname($destination));
        $this->filesystem->copy($sourceFile, $destination);
        $this->addedConfigFiles[] = $this->getDestinationFileWithConfigsPath($destinationFile);
    }

    private function dist(string $sourceFile, string $destinationFile): void
    {
        $this->filesystem->copy($sourceFile,  $this->getDestinationPath($destinationFile . '.dist'));
        $this->copiedConfigFiles[] = $this->getDestinationFileWithConfigsPath($destinationFile);
    }

    private function update(string $sourceFile, string $destinationFile): void
    {
        $this->filesystem->copy($sourceFile, $this->getDestinationPath($destinationFile));
        $this->updatedConfigFiles[] = $this->getDestinationFileWithConfigsPath($destinationFile);
    }

    private function updateMergePlan(array $mergePlan): void
    {
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
            $this->addedConfigFiles = [];
        }

        if ($this->updatedConfigFiles) {
            $this->addMessage($messages, $this->updatedConfigFiles, 'Config files has been updated');
            $this->updatedConfigFiles = [];
        }

        if ($this->copiedConfigFiles) {
            $this->addMessage(
                $messages,
                $this->copiedConfigFiles,
                'Config files has been copied with the ".dist" postfix',
                'Please review files above and change it according with dist files.',
            );
            $this->copiedConfigFiles = [];
        }

        if ($this->ignoredConfigFiles) {
            $this->addMessage(
                $messages,
                $this->ignoredConfigFiles,
                'Config files has been ignored',
                'Please review the files above and change them yourself if necessary.',
            );
            $this->ignoredConfigFiles = [];
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

    private function getDestinationFileWithConfigsPath(string $destinationFile): string
    {
        return $this->configsPath . '/' . $destinationFile;
    }

    private function getDestinationPath(string $destinationFile): string
    {
        return '/' . $this->rootPath . '/' . $this->configsPath . '/' . $destinationFile;
    }
}
