<?php

declare(strict_types=1);

namespace Goblin\Daily;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Executes JQL queries and returns issue keys.
 *
 * @psalm-api
 */
final readonly class JiraSearch
{
    private const int MAX_RESULTS = 50;

    /**
     * Stores HTTP client for Jira API requests.
     *
     * @param Http $http HTTP client.
     */
    public function __construct(private Http $http) {}

    /**
     * Returns issue keys matching a JQL query.
     *
     * @param string $jql JQL query string.
     * @throws GoblinException
     * @return list<string>
     */
    public function keys(string $jql): array
    {
        $query = http_build_query([
            'jql' => $jql,
            'fields' => 'key',
            'maxResults' => self::MAX_RESULTS,
        ]);

        $data = $this->http->json('GET', "/rest/api/3/search/jql?{$query}");

        /** @psalm-var mixed $issues */
        $issues = $data['issues'] ?? [];

        if (!is_array($issues)) {
            return [];
        }

        $keys = [];

        /** @psalm-var mixed $issue */
        foreach ($issues as $issue) {
            if (is_array($issue) && array_key_exists('key', $issue) && is_string($issue['key'])) {
                $keys[] = $issue['key'];
            }
        }

        return $keys;
    }
}
