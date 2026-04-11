<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Provides access to local git state.
 *
 * @psalm-api
 */
interface Git
{
    /**
     * Returns the current branch name.
     *
     * @throws GoblinException
     */
    public function currentBranch(): string;
}
