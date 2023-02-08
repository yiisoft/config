<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

/**
 * Enable recursive merge for specified groups.
 *
 * The modifier should be specified as
 *
 * ```php
 * RecursiveMerge::groups('params', 'events')
 * ```
 *
 * For example:
 *
 * - configuration in application `composer.json`:
 *
 * ```
 * "config-plugin": {
 *     "params": "params.php",
 * }
 * ```
 *
 * - application `params.php` contents:
 *
 * ```php
 * return [
 *     'key' => [
 *        'a' => 1,
 *        'b' => 2,
 *     ],
 * ];
 * ```
 *
 * - configuration in vendor package:
 *
 * ```
 * "config-plugin": {
 *     "params": "params.php",
 * }
 * ```
 *
 * - vendor package `params.php` contents:
 *
 * ```php
 * return [
 *     'key' => [
 *        'c' => 3,
 *        'd' => 4,
 *     ],
 * ];
 * ```
 *
 * - getting configuration:
 *
 * ```php
 * $config = new Config(new ConfigPaths($configsDir), null, [
 *     RecursiveMerge::groups('params')
 * ]);
 *
 * $result = $config->get('params');
 * ```
 *
 * The result will be:
 *
 * ```php
 * [
 *     'key' => [
 *        'c' => 3,
 *        'd' => 4,
 *        'a' => 1,
 *        'b' => 2,
 *     ],
 * ]
 * ```
 */
final class RecursiveMerge
{
    /**
     * @param string[] $groups
     */
    private function __construct(
        private array $groups,
    ) {
    }

    public static function groups(string ...$groups): self
    {
        return new self($groups);
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
