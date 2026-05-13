<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Target branch for a Fix Version and the bases allowed to fork from.
 */
final readonly class BranchTarget
{
    /**
     * Stores merge target, allowed parents, and base-matching strategy.
     *
     *
     * @param string $target Target branch name.
     * @param non-empty-list<string> $bases Allowed base branches.
     * @param BaseMatch $match Base match mode.
     */
    public function __construct(
        public string $target,
        public array $bases,
        public BaseMatch $match = BaseMatch::Strict,
    ) {}

    /**
     * Returns true when the given branch is an accepted base.
     *
     * @param string $branch Branch name to test.
     */
    public function acceptsBase(string $branch): bool
    {
        return in_array($branch, $this->bases, true);
    }
}
