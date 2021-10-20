<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

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
