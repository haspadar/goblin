<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Git\Git;

/**
 * In-memory git state for tests.
 */
final readonly class FakeGit implements Git
{
    public function __construct(private string $branch) {}

    public function currentBranch(): string
    {
        return $this->branch;
    }
}
