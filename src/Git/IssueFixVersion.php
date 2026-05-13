<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;
use Goblin\Http\Http;
use Goblin\Issue\ProjectVersions;

/**
 * Fetches Fix Version and active versions for an issue from Jira.
 */
final readonly class IssueFixVersion
{
    /**
     * Stores HTTP client and issue key.
     *
     * @param Http $http HTTP client.
     * @param string $key Jira issue key.
     */
    public function __construct(private Http $http, private string $key) {}

    /**
     * Returns the first Fix Version name from the issue.
     *
     * @throws GoblinException
     */
    public function name(): string
    {
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$this->key}");

        /** @psalm-var mixed $fields */
        $fields = $payload['fields'] ?? [];

        /** @psalm-var mixed $versions */
        $versions = is_array($fields) ? ($fields['fixVersions'] ?? []) : [];

        if (!is_array($versions) || $versions === []) {
            throw new GoblinException("Issue {$this->key} has no Fix Version");
        }

        /** @psalm-var mixed $first */
        $first = $versions[0];

        /** @psalm-var mixed $name */
        $name = is_array($first) ? ($first['name'] ?? '') : '';

        if (!is_string($name) || $name === '') {
            throw new GoblinException("Issue {$this->key} has no Fix Version");
        }

        return $name;
    }

    /**
     * Returns active unreleased versions for the project.
     *
     * @throws GoblinException
     * @return list<string>
     */
    public function activeVersions(): array
    {
        return (new ProjectVersions($this->http, $this->projectKey()))->names();
    }

    /**
     * Extracts project key from issue key.
     */
    private function projectKey(): string
    {
        $pos = strpos($this->key, '-');

        return is_int($pos)
            ? substr($this->key, 0, $pos)
            : $this->key;
    }
}
