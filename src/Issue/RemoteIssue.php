<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;
use Goblin\Http\Http;
use Override;

/**
 * Loads a single Jira issue from the REST API.
 */
final readonly class RemoteIssue implements Issue
{
    /**
     * Stores HTTP client, issue key, and field discovery.
     */
    public function __construct(
        private Http $http,
        private IssueKey $key,
        private DescriptionFields $fields,
    ) {}

    #[Override]
    public function details(): array
    {
        $key = $this->key->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$key}");

        /** @psalm-var mixed $raw */
        $raw = $payload['fields'] ?? [];

        /** @psalm-var array<string, mixed> $fields */
        $fields = is_array($raw)
            ? $raw
            : [];
        $fields['comment'] = ['comments' => $this->comments($key)];
        $payload['fields'] = $fields;

        return (new JiraIssue($payload, $this->fields->names()))->details();
    }

    #[Override]
    public function description(): string
    {
        $key = $this->key->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$key}");

        return (new JiraIssue($payload, $this->fields->names()))->description();
    }

    #[Override]
    public function raw(): array
    {
        return $this->http->json(
            'GET',
            "/rest/api/3/issue/{$this->key->value()}",
        );
    }

    /**
     * Fetches all comments for an issue with pagination.
     *
     * @throws GoblinException
     * @return list<array<string, mixed>>
     */
    private function comments(string $key): array
    {
        return (new PaginatedComments($this->http, $key))->all();
    }
}
