<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\Git\BranchRules;
use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Unreleased versions with their target branches.
 */
final readonly class VersionsList
{
    /**
     * Stores HTTP client, project key, and branch rules.
     *
     * @psalm-suppress PossiblyUnusedMethod called from bin/jira-releases
     * @param array<string, mixed> $rules
     */
    public function __construct(private Http $http, private string $project, private array $rules) {}

    /**
     * Returns version-to-branch pairs.
     *
     * @throws GoblinException
     * @return list<array{version: string, branch: string}>
     */
    public function pairs(): array
    {
        $versions = (new ProjectVersions($this->http, $this->project))->names();

        if ($versions === []) {
            throw new GoblinException("No unreleased versions found for project {$this->project}");
        }

        $mapping = new BranchRules($versions, $this->rules);
        $result = [];

        foreach ($versions as $version) {
            $result[] = [
                'version' => $version,
                'branch' => $mapping->branchFor($version),
            ];
        }

        return $result;
    }
}
