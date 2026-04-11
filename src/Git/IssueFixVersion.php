<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Fetches Fix Version and active versions for an issue from Jira.
 */
final readonly class IssueFixVersion
{
    /**
     * Stores HTTP client and issue key.
     */
    public function __construct(private Http $http, private string $issueKey) {}

    /**
     * Returns the first Fix Version name from the issue.
     *
     * @throws GoblinException
     */
    public function name(): string
    {
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$this->issueKey}");

        /** @psalm-var mixed $fields */
        $fields = $payload['fields'] ?? [];

        /** @psalm-var mixed $versions */
        $versions = is_array($fields) ? ($fields['fixVersions'] ?? []) : [];

        if (!is_array($versions) || $versions === []) {
            throw new GoblinException("Issue {$this->issueKey} has no Fix Version");
        }

        /** @psalm-var mixed $first */
        $first = $versions[0];

        /** @psalm-var mixed $name */
        $name = is_array($first) ? ($first['name'] ?? '') : '';

        if (!is_string($name) || $name === '') {
            throw new GoblinException("Issue {$this->issueKey} has no Fix Version");
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
        $projectKey = $this->projectKey();

        $allVersions = $this->http->json(
            'GET',
            "/rest/api/3/project/{$projectKey}/version?status=unreleased&orderBy=name",
        );

        $result = [];
        $pattern = '/^' . preg_quote($projectKey, '/') . '\s+\d+\.\d+\.\d+$/';

        /** @psalm-var mixed $version */
        foreach ($allVersions as $version) {
            if (!is_array($version)) {
                continue;
            }

            /** @psalm-var mixed $name */
            $name = $version['name'] ?? '';

            if (is_string($name) && preg_match($pattern, $name) === 1) {
                $result[] = $name;
            }
        }

        usort($result, static fn(string $a, string $b): int => version_compare($a, $b));

        return $result;
    }

    /**
     * Extracts project key from issue key.
     */
    private function projectKey(): string
    {
        $pos = strpos($this->issueKey, '-');

        return $pos !== false
            ? substr($this->issueKey, 0, $pos)
            : $this->issueKey;
    }
}
