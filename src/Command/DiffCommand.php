<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\ConfigFile;
use Yiisoft\Config\ConfigFileDiffer;

use function array_map;
use function array_unique;
use function implode;
use function preg_replace;
use function sprintf;
use function strpos;

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

        foreach ($this->groupPackageFiles($packages, $config, $io) as $package => $configFiles) {
            $differ->diffPackage($package, $configFiles);
        }

        $io->write("\n<info>Done.</info>");
        return 0;
    }

    /**
     * @param string[] $packages
     * @param ComposerConfigProcess $config
     * @param IOInterface $io
     * @return array<string, ConfigFile[]>
     */
    private function groupPackageFiles(array $packages, ComposerConfigProcess $config, IOInterface $io): array
    {
        $processedPackages = array_unique(array_map(static function (ConfigFile $file): string {
            return preg_replace('#^([^/]+/[^/]+)/.*$#', '\1', $file->destinationFile());
        }, $config->configFiles()));

        if (!empty($packages) && !empty($notControlledPackages = array_diff($packages, $processedPackages))) {
            $io->write(sprintf(
                '<error>Package(s) "%s" are not controlled by the config plugin.</error>',
                implode('", "', $notControlledPackages),
            ));
        }

        $groupedPackageFiles = [];

        foreach ($processedPackages as $package) {
            foreach ($config->configFiles() as $configFile) {
                if (strpos($configFile->destinationFile(), $package) !== false) {
                    $groupedPackageFiles[$package][] = $configFile;
                }
            }
        }

        return $groupedPackageFiles;
    }
}
