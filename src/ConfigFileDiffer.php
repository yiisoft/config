<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Composer\IO\IOInterface;
use Jfcherng\Diff\SequenceMatcher;

use function explode;
use function file_get_contents;
use function implode;
use function is_file;
use function strtr;

/**
 * @internal
 */
final class ConfigFileDiffer
{
    /**
     * @var string[]
     */
    private array $diff = [];

    private IOInterface $io;
    private string $configsPath;

    /**
     * @param IOInterface $io The IO instance.
     * @param string $configsPath The full path to directory containing configuration files.
     */
    public function __construct(IOInterface $io, string $configsPath)
    {
        $this->io = $io;
        $this->configsPath = $configsPath;
    }

    /**
     * Displays the difference between the vendor file and the custom configuration file.
     *
     * @param ConfigFile $configFile
     */
    public function diff(ConfigFile $configFile): void
    {
        $packageFile = $this->configsPath . '/' . $configFile->destinationFile();
        $vendorFile = $configFile->sourceFilePath();

        $this->io->write($this->vendorLine("-- $vendorFile"));
        $this->io->write($this->packageLine("++ $packageFile"));

        if (!$this->fileExists($packageFile) || !$this->fileExists($vendorFile)) {
            return;
        }

        $packageLines = explode("\n", $this->normalizeLineEndings(file_get_contents($packageFile)));
        $vendorLines = explode("\n", $this->normalizeLineEndings(file_get_contents($vendorFile)));

        foreach ((new SequenceMatcher($vendorLines, $packageLines))->getGroupedOpcodes() as $groupedOpcodes) {
            foreach ($groupedOpcodes as $groupedOpcode) {
                [$tag, $vendorFirstLine, $vendorLastLine, $packageFirstLine, $packageLastLine] = $groupedOpcode;

                if ($tag === SequenceMatcher::OP_DEL) {
                    $this->addCommentLine("-$vendorFirstLine,$vendorLastLine");
                    $this->addVendorLines($vendorLines, $vendorFirstLine, $vendorLastLine);
                    continue;
                }

                if ($tag === SequenceMatcher::OP_INS) {
                    $this->addCommentLine("+$packageFirstLine,$packageLastLine");
                    $this->addPackageLines($packageLines, $packageFirstLine, $packageLastLine);
                    continue;
                }

                if ($tag === SequenceMatcher::OP_REP) {
                    $this->addCommentLine("-$vendorFirstLine,$vendorLastLine +$packageFirstLine,$packageLastLine");
                    $this->addVendorLines($vendorLines, $vendorFirstLine, $vendorLastLine);
                    $this->addPackageLines($packageLines, $packageFirstLine, $packageLastLine);
                }
            }
        }

        if (empty($this->diff)) {
            $this->io->write('<fg=white>No differences.</>');
            return;
        }

        $this->io->write(implode("\n", $this->diff));
        $this->diff = [];
    }

    /**
     * Checks whether the contents are equal to.
     *
     * @param string $a
     * @param string $b
     *
     * @return bool
     */
    public function isContentEqual(string $a, string $b): bool
    {
        return $this->normalizeLineEndings($a) === $this->normalizeLineEndings($b);
    }

    private function addCommentLine(string $content): void
    {
        $this->diff[] = $this->commentLine($content);
    }

    /**
     * @param string[] $lines
     * @param int $firstLine
     * @param int $lastLine
     */
    private function addPackageLines(array $lines, int $firstLine, int $lastLine): void
    {
        for ($i = $firstLine; $i < $lastLine; $i++) {
            $this->diff[] = $this->packageLine($lines[$i]);
        }
    }

    /**
     * @param string[] $lines
     * @param int $firstLine
     * @param int $lastLine
     */
    private function addVendorLines(array $lines, int $firstLine, int $lastLine): void
    {
        for ($i = $firstLine; $i < $lastLine; $i++) {
            $this->diff[] = $this->vendorLine($lines[$i]);
        }
    }

    private function commentLine(string $content): string
    {
        return "<fg=yellow>= Lines: $content =</>";
    }

    private function packageLine(string $content): string
    {
        return "<fg=green>+$content</>";
    }

    private function vendorLine(string $content): string
    {
        return "<fg=red>-$content</>";
    }

    private function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }

    private function fileExists(string $file): bool
    {
        if (is_file($file)) {
            return true;
        }

        $this->io->write("<error>The file \"$file\" does not exist or is not a file.</error>");
        return false;
    }
}
