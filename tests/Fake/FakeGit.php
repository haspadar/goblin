<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Git\Git;

/**
 * In-memory git state for tests.
 */
final readonly class FakeGit implements Git
{
    public function __construct(
        private string $branch,
        private string $parent = 'main',
        private string $remote = 'git@gitlab.example.com:group/project.git',
    ) {}

    public function currentBranch(): string
    {
        return $this->branch;
    }

    public function parentBranch(): string
    {
        return $this->parent;
    }

    public function remote(): string
    {
        return $this->remote;
    }
}
