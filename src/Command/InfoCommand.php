<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Config\Composer\Options;
use Yiisoft\Config\Composer\RootConfiguration;

final class InfoCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('yii-config-info')
            ->addArgument('type', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = RootConfiguration::fromComposerInstance($this->getComposer());
        $io = new SymfonyStyle($input, $output);

        return match ($input->getArgument('type')) {
            default => $this->default($configuration, $io),
        };
    }

    private function default(RootConfiguration $configuration, SymfonyStyle $io): int
    {
        $options = $configuration->options();
        $mergePlanFilePath = $configuration->path() . '/' . $options->mergePlanFile();

        $io->title('Yii Config — Composer Data');

        $io->section('Options');
        $io->table([], [
            [
                'Build merge plan',
                $options->buildMergePlan() ? '<fg=green>yes</>' : '<fg=red>no</>',
            ],
            [
                'Merge plan file path',
                file_exists($mergePlanFilePath)
                    ? '<fg=green>' . $mergePlanFilePath . '</>'
                    : '<fg=red>' . $mergePlanFilePath . ' (not exists)</>',
            ],
            [
                'Package types',
                empty($options->packageTypes()) ? '<fg=red>not set</>' : implode(', ', $options->packageTypes()),
            ],
            [
                'Source directory',
                $configuration->path() . '/' . $options->sourceDirectory(),
            ],
            [
                'Vendor override layer packages',
                empty($options->vendorOverrideLayerPackages())
                    ? '<fg=gray>not set</>'
                    : implode(', ', $options->vendorOverrideLayerPackages()),
            ],
        ]);

        $io->section('Configuration groups');
        $this->writeConfiguration($io, $configuration->packageConfiguration(), 1, true);

        $io->section('Environments');
        $environmentsConfiguration = $configuration->environmentsConfiguration();
        if (empty($environmentsConfiguration)) {
            $io->writeln('<fg=gray>not set</>');
        } else {
            $isFirst = true;
            foreach ($environmentsConfiguration as $environment => $groups) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $io->newLine();
                }
                $io->write(' <fg=bright-magenta>' . $environment . '</>');
                if (empty($groups)) {
                    $io->writeln(' <fg=gray>(empty)</>');
                } else {
                    $io->newLine();
                    $this->writeConfiguration($io, $groups, 2, false);
                }
            }
        }

        return 0;
    }

    private function writeConfiguration(
        SymfonyStyle $io,
        array $configuration,
        int $offset,
        bool $addSeparateLine
    ): void {
        foreach ($configuration as $group => $values) {
            $this->writeGroup($io, $group, $values, $offset);
            if ($addSeparateLine) {
                $io->newLine();
            }
        }
    }

    /**
     * @param string[]|string $items
     */
    private function writeGroup(SymfonyStyle $io, string $group, array|string $items, int $offset): void
    {
        $prefix = str_repeat(' ', $offset);
        $items = (array) $items;
        $io->write($prefix . '<fg=cyan>' . $group . '</>');
        if (empty($items)) {
            $io->write(' <fg=gray>(empty)</>');
        } else {
            foreach ($items as $item) {
                $io->newLine();
                $io->write($prefix . ' - ' . (Options::isVariable($item) ? '<fg=green>' . $item . '</>' : $item));
            }
        }
        $io->newLine();
    }
}
