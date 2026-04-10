<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;

/**
 * Single Jira issue abstraction.
 *
 * @psalm-api
 */
interface Issue
{
    /**
     * Returns structured issue representation.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function details(): array;

    /**
     * Returns plain-text description of the issue.
     *
     * @throws GoblinException
     */
    public function description(): string;

    /**
     * Returns the raw Jira payload.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function raw(): array;
}
