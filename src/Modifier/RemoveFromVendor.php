<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

/**
 * Marks specified keys to be ignored when reading configuration from vendor packages.
 * Nested keys are supported only when {@see RecursiveMerge} modifier is applied as well.
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
     * @param string[] ...$keys
     */
    public static function keys(array ...$keys): RemoveKeysFromVendor
    {
        return new RemoveKeysFromVendor(...$keys);
    }
}
