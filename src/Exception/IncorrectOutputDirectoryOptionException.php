<?php

declare(strict_types=1);

namespace Yiisoft\Config\Exception;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class IncorrectOutputDirectoryOptionException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Option "output-directory" should be not empty string.');
    }

    public function getName(): string
    {
        return 'Incorrect value of option "output-directory".';
    }

    public function getSolution(): ?string
    {
        return 'You have incorrect value of option "output-directory" in composer.json.' . "\n" .
            'Open composer.json file and change value of key "extra → config-plugin-options → output-directory" to ' .
            'relative path to output directory which will be assembled configs.';
    }
}
