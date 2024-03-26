<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
            'options' => $this->options($configuration, $io),
            default => $this->default($configuration),
        };
    }

    private function default(RootConfiguration $configuration): int
    {
        echo 'Need type info.';
        return 0;
    }

    private function options(RootConfiguration $configuration, SymfonyStyle $io): int
    {
        $options = $configuration->options();
        $mergePlanFilePath = $configuration->path() . '/' . $options->mergePlanFile();

        $io->title('Yii Config Options');

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
            ['Package types', implode(', ', $options->packageTypes())],
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

        return 0;
    }
}
