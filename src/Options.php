<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use function is_array;

/**
 * @internal
 */
final class Options
{
    private bool $silentOverride;
    private bool $forceCheck;
    private string $outputDirectory;

    public function __construct(array $extra)
    {
        /** @var mixed */
        $options = $extra['config-plugin-options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $this->silentOverride = (bool)($options['silent-override'] ?? false);
        $this->forceCheck = (bool)($options['force-check'] ?? false);
        $this->outputDirectory = (string)($options['output-directory'] ?? 'config/packages');
    }

    public function silentOverride(): bool
    {
        return $this->silentOverride;
    }

    public function forceCheck(): bool
    {
        return $this->forceCheck;
    }

    public function outputDirectory(): string
    {
        return $this->outputDirectory;
    }
}
