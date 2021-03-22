<?php

declare(strict_types=1);

namespace Yiisoft\Config\Exception;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function gettype;

final class IncorrectSilentOverrideOptionException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        parent::__construct('Option "silent-override" should be boolean. Current is ' . gettype($value) . '.');
    }

    public function getName(): string
    {
        return 'Incorrect value of option "silent-override".';
    }

    public function getSolution(): ?string
    {
        return 'You have incorrect value of option "silent-override" in composer.json.' . "\n" .
            'Open composer.json file and change value of key "extra → config-plugin-options → silent-override" to:' . "\n" .
            ' - `true` for enable silent override configuration files;' . "\n" .
            ' - `false` for manually change configuration files.';
    }
}
