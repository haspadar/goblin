<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Fetches all comments for a Jira issue with pagination.
 */
final readonly class PaginatedComments
{
    /**
     * Stores HTTP client and issue key for comment loading.
     */
    public function __construct(private Http $http, private string $key) {}

    /**
     * Returns all comments across paginated API responses.
     *
     * @throws GoblinException
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $result = [];
        $startAt = 0;
        $maxResults = 100;

        do {
            $page = $this->http->json(
                'GET',
                "/rest/api/3/issue/{$this->key}/comment?startAt={$startAt}&maxResults={$maxResults}",
            );

            $result = [...$result, ...$this->extractComments($page)];
            $pageSize = $this->pageSize($page, $maxResults);
            $startAt += $pageSize;
        } while ($startAt < $this->int($page, 'total'));

        return $result;
    }

    /**
     * Extracts valid comment arrays from a page response.
     *
     * @param array<string, mixed> $page
     * @return list<array<string, mixed>>
     */
    private function extractComments(array $page): array
    {
        /** @psalm-var mixed $items */
        $items = $page['comments'] ?? [];
        $result = [];

        if (!is_array($items)) {
            return [];
        }

        /** @psalm-var mixed $item */
        foreach ($items as $item) {
            if (is_array($item)) {
                /** @phpstan-var array<string, mixed> $item */
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Returns page size from response or default.
     *
     * @param array<string, mixed> $page
     */
    private function pageSize(array $page, int $default): int
    {
        $size = $this->int($page, 'maxResults');

        return $size > 0
            ? $size
            : $default;
    }

    /**
     * Extracts an integer value from response array.
     *
     * @param array<string, mixed> $data
     */
    private function int(array $data, string $key): int
    {
        /** @psalm-var mixed $value */
        $value = $data[$key] ?? 0;

        return is_int($value)
            ? $value
            : 0;
    }
}
