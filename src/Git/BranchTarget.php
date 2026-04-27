<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Target branch for a Fix Version and the bases allowed to fork from.
 */
final readonly class BranchTarget
{
    /**
     * Stores merge target and list of allowed parent branches.
     *
     * @param non-empty-list<string> $bases
     */
    public function __construct(
        public string $target,
        public array $bases,
    ) {}

    /**
     * Returns true when the given branch is an accepted base.
     */
    public function acceptsBase(string $branch): bool
    {
        return in_array($branch, $this->bases, true);
    }
}
