<?php

declare(strict_types=1);

namespace Goblin\MergeRequest;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Manages GitLab merge requests via API v4.
 *
 * @psalm-api
 */
final readonly class GitLabMergeRequest
{
    /**
     * Stores HTTP client and encoded project path.
     */
    public function __construct(private Http $http, private string $project) {}

    /**
     * Creates a new merge request.
     *
     * @param array<string, string> $params
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->http->json('POST', $this->path(), $params);
    }

    /**
     * Returns a single merge request by IID.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function view(int $iid): array
    {
        return $this->http->json('GET', $this->path("/{$iid}"));
    }

    /**
     * Lists merge requests with optional query filters.
     *
     * @param array<string, string> $filters
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        $clean = array_filter($filters, static fn(string $v): bool => $v !== '');
        $query = $clean === []
            ? ''
            : '?' . http_build_query($clean);

        return $this->http->json('GET', $this->path($query));
    }

    /**
     * Updates a merge request by IID.
     *
     * @param array<string, string> $changes
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function update(int $iid, array $changes): array
    {
        return $this->http->json('PUT', $this->path("/{$iid}"), $changes);
    }

    /**
     * Builds API path for the project's merge requests.
     */
    private function path(string $suffix = ''): string
    {
        return '/projects/' . rawurlencode($this->project) . '/merge_requests' . $suffix;
    }
}
