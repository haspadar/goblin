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
     * @param IssueKey $issueKey Jira issue key.
     * @param DescriptionFields $fields Description field discovery.
     */
    public function __construct(
        private Http $http,
        private IssueKey $issueKey,
        private DescriptionFields $fields,
    ) {}

    #[Override]
    public function details(): array
    {
        $key = $this->issueKey->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$key}");

        /** @psalm-var mixed $raw */
        $raw = $payload['fields'] ?? [];

        /** @psalm-var array<string, mixed> $payloadFields */
        $payloadFields = is_array($raw)
            ? $raw
            : [];
        $payloadFields['comment'] = ['comments' => $this->comments($key)];
        $payload['fields'] = $payloadFields;

        return (new JiraIssue($payload, $this->fields->names()))->details();
    }

    #[Override]
    public function description(): string
    {
        $key = $this->issueKey->value();
        $payload = $this->http->json('GET', "/rest/api/3/issue/{$key}");

        return (new JiraIssue($payload, $this->fields->names()))->description();
    }

    #[Override]
    public function raw(): array
    {
        return $this->http->json(
            'GET',
            "/rest/api/3/issue/{$this->issueKey->value()}",
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
