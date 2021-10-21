<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

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
