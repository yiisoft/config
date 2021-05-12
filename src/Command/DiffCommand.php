<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\ConfigFile;
use Yiisoft\Config\ConfigFileDiffer;

use function array_map;
use function array_unique;
use function file_get_contents;
use function implode;
use function is_file;
use function sort;
use function sprintf;

/**
 * @internal
 */
final class DiffCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('config-diff')
            ->setDescription('Displaying differences in Config Files')
            ->setHelp('This command displays the differences in the configuration files.')
            ->addArgument('packages', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Packages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIo();
        /** @var string[] $packages */
        $packages = $input->getArgument('packages');
        /** @psalm-suppress  PossiblyNullArgument */
        $process = new ComposerConfigProcess($this->getComposer(), $packages, empty($packages));
        $differ = new ConfigFileDiffer($io, "{$process->rootPath()}/{$process->configsDirectory()}");

        foreach ($this->groupPackageFiles($packages, $process, $differ) as $package => $configFiles) {
            $io->write("\n<bg=magenta;fg=white;options=bold>= $package =</>\n");

            foreach ($configFiles as $configFile) {
                $differ->diff($configFile);
            }
        }

        return 0;
    }

    /**
     * @param string[] $packages
     * @param ComposerConfigProcess $process
     * @param ConfigFileDiffer $differ
     *
     * @return array<string, ConfigFile[]>
     */
    private function groupPackageFiles(array $packages, ComposerConfigProcess $process, ConfigFileDiffer $differ): array
    {
        $processedPackages = array_unique(array_map(static function (ConfigFile $configFile): string {
            return $configFile->package()->getPrettyName();
        }, $process->configFiles()));

        if (!empty($packages) && !empty($notControlledPackages = array_diff($packages, $processedPackages))) {
            $this->getIo()->write(sprintf(
                '<error>Package(s) "%s" are not controlled by the config plugin.</error>',
                implode('", "', $notControlledPackages),
            ));
        }

        $destinationDirectoryPath = "{$process->rootPath()}/{$process->configsDirectory()}";
        $groupedPackageFiles = [];
        sort($processedPackages);

        foreach ($processedPackages as $package) {
            foreach ($process->configFiles() as $configFile) {
                if ($configFile->package()->getPrettyName() === $package) {
                    $destinationFile = "{$destinationDirectoryPath}/{$configFile->destinationFile()}";
                    $sourceFile = $configFile->sourceFilePath();

                    if (!is_file($destinationFile) || !is_file($sourceFile)) {
                        $groupedPackageFiles[$package][] = $configFile;
                        continue;
                    }

                    if (!$differ->isContentEqual(file_get_contents($destinationFile), file_get_contents($sourceFile))) {
                        $groupedPackageFiles[$package][] = $configFile;
                    }
                }
            }
        }

        return $groupedPackageFiles;
    }
}
