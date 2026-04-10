<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;

/**
 * Normalized Jira issue key from user input.
 *
 * @psalm-api
 */
final readonly class IssueKey
{
    /**
     * Stores user input and optional project prefix.
     */
    public function __construct(private string $input, private string $project = '') {}

    /**
     * Returns the normalized issue key.
     *
     * @throws GoblinException
     */
    public function value(): string
    {
        $trimmed = trim($this->input);

        if ($trimmed === '') {
            throw new GoblinException('Issue key must not be empty');
        }

        return preg_match('/^\d+$/', $trimmed) === 1
            ? $this->withProject($trimmed)
            : strtoupper($trimmed);
    }

    /**
     * Prepends project prefix to a numeric key.
     *
     * @throws GoblinException
     */
    private function withProject(string $number): string
    {
        $project = strtoupper(trim($this->project));

        if ($project === '') {
            throw new GoblinException(
                "Cannot resolve numeric key \"{$number}\" without a project prefix",
            );
        }

        return $project . '-' . $number;
    }
}
