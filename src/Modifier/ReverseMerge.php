<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

/**
 * Result of reverse merge is be ordered descending by data source. It is useful for merging module
 * config with base config where more specific config (i.e. module's) has more priority.
 *
 * The modifier should be specified as
 *
 * ```php
 * ReverseMerge::groups('events', 'events-web', 'events-console')
 * ```
 *
 * For example:
 *
 * - configuration in application `composer.json`:
 *
 * ```
 * "config-plugin": {
 *     "events": "events.php",
 *     "params": "params.php",
 * }
 * ```
 *
 * - application `events.php` contents:
 *
 * ```php
 * return ['a' => 1, 'b' => 2];
 * ```
 *
 * - configuration in vendor package:
 *
 * ```
 * "config-plugin": {
 *     "events": "events.php",
 * }
 * ```
 *
 * - vendor package `events.php` contents:
 *
 * ```php
 * return ['c' => 3, 'd' => 4];
 * ```
 *
 * - getting configuration:
 *
 * ```php
 * $config = new Config(new ConfigPaths($configsDir), null, [
 *     ReverseMerge::groups('events'),
 * ]);
 *
 * $result = $config->get('events');
 * ```
 *
 * The result will be:
 *
 * ```php
 * [
 *     'a' => 1,
 *     'b' => 2,
 *     'c' => 3,
 *     'd' => 3,
 * ]
 * ```
 */
final class ReverseMerge
{
    /**
     * @var string[]
     */
    private array $groups;

    /**
     * @param string[] $groups
     */
    private function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    public static function groups(string ...$groups): self
    {
        return new self($groups);
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}