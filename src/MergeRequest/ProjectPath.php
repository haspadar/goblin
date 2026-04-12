<?php

declare(strict_types=1);

namespace Goblin\MergeRequest;

use Goblin\GoblinException;

/**
 * Extracts project path from git remote URL.
 *
 * @psalm-api
 */
final readonly class ProjectPath
{
    /**
     * Stores the raw remote URL.
     */
    public function __construct(private string $remote) {}

    /**
     * Returns group/project path suitable for GitLab API.
     *
     * @throws GoblinException
     */
    public function value(): string
    {
        if (preg_match('#^[^@]+@[^:]+:(.+?)(?:\.git)?$#', $this->remote, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('#^https?://[^/]+/(.+?)(?:\.git)?$#', $this->remote, $matches) === 1) {
            return $matches[1];
        }

        throw new GoblinException(
            "Cannot extract project path from remote: {$this->remote}",
        );
    }
}
