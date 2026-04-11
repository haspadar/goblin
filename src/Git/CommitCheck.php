<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Validates commit message against branch issue key.
 */
final readonly class CommitCheck
{
    /**
     * Stores branch name, commit message, and project regex.
     *
     * @param non-empty-string $projectRegex
     */
    public function __construct(
        private string $branch,
        private string $message,
        private string $projectRegex,
    ) {}

    /**
     * Validates and throws on mismatch.
     *
     * @throws GoblinException
     */
    public function validate(): void
    {
        if ($this->isMergeCommit()) {
            return;
        }

        $branchKey = $this->keyFrom($this->branch);
        $messageKey = $this->keyFrom($this->message);

        if ($branchKey === '' && $messageKey === '') {
            return;
        }

        if ($branchKey === '') {
            throw new GoblinException(
                "Commit message contains {$messageKey}, but branch has no issue key",
            );
        }

        if ($messageKey === '') {
            throw new GoblinException(
                "Branch contains {$branchKey}, but commit message has no issue key",
            );
        }

        if ($branchKey !== $messageKey) {
            throw new GoblinException(
                "Branch issue key ({$branchKey}) differs from commit message ({$messageKey})",
            );
        }
    }

    private function isMergeCommit(): bool
    {
        return preg_match(
            '/^Merge (branch|remote-tracking branch|pull request)\b/i',
            $this->message,
        ) === 1;
    }

    /**
     * Extracts issue key from text using project regex.
     *
     * @throws GoblinException
     */
    private function keyFrom(string $text): string
    {
        $result = @preg_match($this->projectRegex, $text, $m);

        if ($result === false) {
            throw new GoblinException(
                "Invalid project regex: {$this->projectRegex}",
            );
        }

        return $result === 1
            ? $m[0]
            : '';
    }
}
