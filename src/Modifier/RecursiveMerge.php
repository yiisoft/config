<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

use Yiisoft\Arrays\ArrayHelper;

final class RecursiveMerge
{
    /**
     * @var string[]
     */
    private array $groups;

    /**
     * @var string[][]
     * @psalm-var array<array-key,list<string>>
     */
    private array $removeFromVendorKeys = [];

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

    /**
     * @param string[][] ...$keys
     * @psalm-param list<string> ...$keys
     */
    public function removeFromVendor(array ...$keys): self
    {
        $this->removeFromVendorKeys = $keys;
        return $this;
    }

    public function applyForVendorParams(array $params): void
    {
        foreach ($this->removeFromVendorKeys as $key) {
            ArrayHelper::remove($params, $key);
        }
    }
}
