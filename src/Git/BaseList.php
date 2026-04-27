<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Allowed base branches extracted from a branch-rule config entry.
 */
final readonly class BaseList
{
    /**
     * Stores rule config and target branch used as fallback.
     *
     * @param array<array-key, mixed> $rule
     */
    public function __construct(private array $rule, private string $target) {}

    /**
     * Returns non-empty list of allowed base branches.
     *
     * @return non-empty-list<string>
     */
    public function toList(): array
    {
        /** @psalm-var mixed $base */
        $base = $this->rule['base'] ?? null;
        $list = is_string($base)
            ? [$base]
            : (is_array($base) ? array_values(array_filter($base, 'is_string')) : []);
        $filtered = array_values(array_filter($list, static fn(string $b): bool => $b !== ''));

        return $filtered === [] ? [$this->target] : $filtered;
    }
}
