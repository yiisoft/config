<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Config\Composer\ProcessHelper;

/**
 * `ConfigCommand`
 */
final class ConfigCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('yii-config-merge-plan');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress DeprecatedMethod
         */
        $helper = new ProcessHelper($this->getComposer());
        $output->write(
            $helper->getPaths()->absolute(
                $helper->getMergePlanFile()
            )
        );

        return 0;
    }
}
