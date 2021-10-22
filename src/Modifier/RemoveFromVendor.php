<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

/**
 * In process getting configuration all elements of arrays from vendor packages with specified key paths
 * will be ignored. Support for nested keys only works in conjunction with {@see RecursiveMerge} modifier.
 *
 * The modifier should be specified as
 *
 * ```php
 * RemoveFromVendor::keys(
 *     ['key-for-remove'],
 *     ['nested', 'key', 'for-remove'],
 * )
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
 * return ['c' => 3, 'd' => 4, 'e' => 5];
 * ```
 *
 * - getting configuration:
 *
 * ```php
 * $config = new Config(new ConfigPaths($configsDir), null, [
 *     RemoveFromVendor::keys(
 *         ['d'],
 *         ['e'],
 *     )
 * ]);
 *
 * $result = $config->get('events');
 * ```
 *
 * The result will be:
 *
 * ```php
 * [
 *     'c' => 3,
 *     'a' => 1,
 *     'b' => 2,
 * ]
 * ```
 */
final class RemoveFromVendor
{
    /**
     * @var string[][]
     */
    private array $keys;

    /**
     * @param string[][] $keys
     */
    private function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @param string[] ...$keys
     */
    public static function keys(array ...$keys): self
    {
        return new self($keys);
    }

    /**
     * @return string[][]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }
}
