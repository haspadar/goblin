<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\Config\Config;
use Goblin\Git\Git;
use Goblin\GoblinException;

/**
 * Resolves project key from argument or current branch.
 *
 * @psalm-api
 */
final readonly class ProjectKey
{
    /**
     * Stores argument, git state, and configuration.
     */
    public function __construct(private string $argument, private Git $git, private Config $config) {}

    /**
     * Returns resolved project key.
     *
     * @throws GoblinException
     */
    public function value(): string
    {
        $trimmed = strtoupper(trim($this->argument));

        if ($trimmed !== '') {
            return $trimmed;
        }

        return $this->fromBranch();
    }

    /**
     * Extracts project key from current branch via project-regex.
     *
     * @throws GoblinException
     */
    private function fromBranch(): string
    {
        /** @psalm-var non-empty-string $regex */
        $regex = $this->config->value('project-regex');
        $branch = $this->git->currentBranch();
        $result = @preg_match($regex, $branch, $matches);

        if ($result === false) {
            throw new GoblinException("Invalid project regex: {$regex}");
        }

        $project = $result === 1 && array_key_exists(1, $matches)
            ? trim($matches[1])
            : '';

        if ($project === '') {
            throw new GoblinException(
                "Project not specified and cannot be detected from branch '{$branch}'",
            );
        }

        return strtoupper($project);
    }
}
