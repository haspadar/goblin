<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Unreleased versions for a Jira project.
 */
final readonly class ProjectVersions
{
    private const int DEFAULT_PAGE_SIZE = 50;

    /**
     * Stores HTTP client and project key.
     *
     * @param Http $http HTTP client.
     * @param string $project Project identifier.
     */
    public function __construct(private Http $http, private string $project) {}

    /**
     * Returns active unreleased versions sorted by semver.
     *
     * @throws GoblinException
     * @return list<string>
     */
    public function names(): array
    {
        $allVersions = $this->fetchAllPages();

        $result = [];
        $pattern = sprintf('/^%s\s+\d+\.\d+\.\d+$/', preg_quote($this->project, '/'));

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
     * Fetches all pages of unreleased versions from Jira API.
     *
     * @throws GoblinException
     * @return list<mixed>
     */
    private function fetchAllPages(): array
    {
        $startAt = 0;
        $allVersions = [];

        do {
            $response = $this->fetchPage($startAt);

            /** @psalm-var mixed $values */
            $values = $response['values'] ?? [];
            $allVersions = array_merge($allVersions, is_array($values) ? $values : []);

            /** @psalm-var mixed $isLast */
            $isLast = $response['isLast'] ?? true;
            $step = $this->pageSize($response);

            if ($step <= 0) {
                break;
            }

            $startAt += $step;
        } while (!is_bool($isLast) || !$isLast);

        /** @psalm-var list<mixed> */
        return $allVersions;
    }

    /**
     * Fetches a single page of versions.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    private function fetchPage(int $startAt): array
    {
        return $this->http->json(
            'GET',
            "/rest/api/3/project/{$this->project}/version?status=unreleased&orderBy=name&startAt={$startAt}",
        );
    }

    /**
     * Extracts page size from response.
     *
     * @param array<string, mixed> $response Response value.
     */
    private function pageSize(array $response): int
    {
        /** @psalm-var mixed $maxResults */
        $maxResults = $response['maxResults'] ?? self::DEFAULT_PAGE_SIZE;

        return is_int($maxResults)
            ? $maxResults
            : self::DEFAULT_PAGE_SIZE;
    }
}
