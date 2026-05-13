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
     *
     * @param Http $http HTTP client.
     * @param IssueKey $key Identifier key.
     * @param DescriptionFields $fields Issue fields payload.
     */
    public function __construct(
        private Http $http,
        private IssueKey $key,
        private DescriptionFields $fields,
    ) {}

    #[Override]
    public function details(): array
    {
        $identifier = $this->key->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$identifier}");

        /** @psalm-var mixed $raw */
        $raw = $payload['fields'] ?? [];

        /** @psalm-var array<string, mixed> $payloadFields */
        $payloadFields = is_array($raw)
            ? $raw
            : [];
        $payloadFields['comment'] = ['comments' => $this->comments($identifier)];
        $payload['fields'] = $payloadFields;

        return (new JiraIssue($payload, $this->fields->names()))->details();
    }

    #[Override]
    public function description(): string
    {
        $identifier = $this->key->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$identifier}");

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
    private function comments(string $identifier): array
    {
        return (new PaginatedComments($this->http, $identifier))->all();
    }
}
