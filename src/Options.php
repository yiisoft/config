<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Yiisoft\Config\Exception\IncorrectOutputDirectoryOptionException;
use Yiisoft\Config\Exception\IncorrectSilentOverrideOptionException;

use function is_array;
use function is_bool;
use function is_string;

/**
 * @internal
 */
final class Options
{
    private bool $silentOverride;
    private string $outputDirectory;

    public function __construct(array $extra)
    {
        /** @var mixed */
        $options = $extra['config-plugin-options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $silentOverride = $options['silent-override'] ?? false;
        if (!is_bool($silentOverride)) {
            throw new IncorrectSilentOverrideOptionException($silentOverride);
        }
        $this->silentOverride = $silentOverride;

        $outputDirectory = $options['output-directory'] ?? 'config/packages';
        if (!is_string($outputDirectory) || $outputDirectory === '') {
            throw new IncorrectOutputDirectoryOptionException();
        }
        $this->outputDirectory = $outputDirectory;
    }

    public function silentOverride(): bool
    {
        return $this->silentOverride;
    }

    public function outputDirectory(): string
    {
        return $this->outputDirectory;
    }
}
