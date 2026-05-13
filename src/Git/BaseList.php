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
        $list = $this->candidates();
        $filtered = array_values(array_filter($list, static fn(string $b): bool => $b !== ''));

        return $filtered === [] ? [$this->target] : $filtered;
    }

    /**
     * Returns string candidates extracted from the rule's base entry.
     *
     * @return list<string>
     */
    private function candidates(): array
    {
        /** @psalm-var mixed $base */
        $base = $this->rule['base'] ?? null;

        if (is_string($base)) {
            return [$base];
        }

        if (is_array($base)) {
            return array_values(array_filter($base, 'is_string'));
        }

        return [];
    }
}
