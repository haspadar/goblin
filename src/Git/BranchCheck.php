<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\Config\Config;
use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Validates branch was created from correct base per Fix Version.
 */
final readonly class BranchCheck
{
    /**
     * Stores dependencies for branch validation.
     */
    public function __construct(
        private Git $git,
        private Http $http,
        private Config $config,
    ) {}

    /**
     * Validates current branch origin against Jira Fix Version.
     *
     * @throws GoblinException
     */
    public function validate(): void
    {
        $branch = $this->git->currentBranch();

        if ($this->isProtected($branch)) {
            return;
        }

        $issueKey = $this->issueKey($branch);

        if ($issueKey === '') {
            return;
        }

        $issue = new IssueFixVersion($this->http, $issueKey);
        $fixVersion = $issue->name();
        $versions = $issue->activeVersions();
        $rules = $this->config->has('branch-rules')
            ? $this->config->map('branch-rules')
            : [];
        $target = (new BranchRules($versions, $rules))->branchFor($fixVersion);
        $parent = $this->git->parentBranch();

        if ($parent !== $target) {
            throw new GoblinException(
                "Fix Version '{$fixVersion}' requires base '{$target}', but branch was created from '{$parent}'",
            );
        }
    }

    /**
     * Checks if branch is in protected list.
     *
     * @throws GoblinException
     */
    private function isProtected(string $branch): bool
    {
        return in_array(
            $branch,
            $this->config->values('protected-branches'),
            true,
        );
    }

    /**
     * Extracts issue key from branch name using project regex.
     *
     * @throws GoblinException
     */
    private function issueKey(string $branch): string
    {
        /** @psalm-var non-empty-string $regex */
        $regex = $this->config->value('project-regex');

        $result = @preg_match($regex, $branch, $m);

        if ($result === false) {
            throw new GoblinException("Invalid project regex: {$regex}");
        }

        return $result === 1 ? $m[0] : '';
    }
}
