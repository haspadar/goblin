<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\Git\VersionMapping;
use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Unreleased versions with their target branches.
 */
final readonly class VersionsList
{
    /**
     * Stores HTTP client and project key.
     */
    public function __construct(private Http $http, private string $project) {}

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

        $mapping = new VersionMapping($versions);
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
