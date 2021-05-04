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
use function preg_replace;
use function sort;
use function sprintf;
use function strpos;

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
        $config = new ComposerConfigProcess($this->getComposer(), $packages, empty($packages));
        $differ = new ConfigFileDiffer($io, "{$config->rootPath()}/{$config->configsDirectory()}");

        foreach ($this->groupPackageFiles($packages, $config, $differ) as $package => $configFiles) {
            $io->write("\n<bg=magenta;fg=white;options=bold>= $package =</>\n");

            foreach ($configFiles as $configFile) {
                $differ->diff($configFile);
            }
        }

        $io->write("\n<bg=green;fg=black;options=bold>Done.</>");
        return 0;
    }

    /**
     * @param string[] $packages
     * @param ComposerConfigProcess $config
     * @param ConfigFileDiffer $differ
     *
     * @return array<string, ConfigFile[]>
     */
    private function groupPackageFiles(array $packages, ComposerConfigProcess $config, ConfigFileDiffer $differ): array
    {
        $processedPackages = array_unique(array_map(static function (ConfigFile $configFile): string {
            return preg_replace('#^([^/]+/[^/]+)/.*$#', '\1', $configFile->destinationFile());
        }, $config->configFiles()));

        if (!empty($packages) && !empty($notControlledPackages = array_diff($packages, $processedPackages))) {
            $this->getIo()->write(sprintf(
                '<error>Package(s) "%s" are not controlled by the config plugin.</error>',
                implode('", "', $notControlledPackages),
            ));
        }

        $destinationDirectoryPath = "{$config->rootPath()}/{$config->configsDirectory()}";
        $groupedPackageFiles = [];
        sort($processedPackages);

        foreach ($processedPackages as $package) {
            foreach ($config->configFiles() as $configFile) {
                if (strpos($configFile->destinationFile(), $package) !== false) {
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
