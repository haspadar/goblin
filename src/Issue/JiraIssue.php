<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Override;

/**
 * Jira issue built from API payload.
 */
final readonly class JiraIssue implements Issue
{
    /**
     * Stores Jira payload and ADF field candidates.
     *
     * @param array<string, mixed> $payload
     * @param list<string> $descriptionFields
     */
    public function __construct(private array $payload, private array $descriptionFields) {}

    #[Override]
    public function details(): array
    {
        $fields = $this->fields();

        /** @psalm-var mixed $raw */
        $raw = $fields['comment'] ?? [];

        /** @psalm-var mixed $comments */
        $comments = is_array($raw)
            ? ($raw['comments'] ?? [])
            : [];

        return [
            'key' => $this->payload['key'] ?? '',
            'summary' => $fields['summary'] ?? '',
            'description' => $this->description(),
            'comments' => (new JiraComments(
                is_array($comments) ? array_values($comments) : [],
            ))->rendered(),
        ];
    }

    #[Override]
    public function description(): string
    {
        $fields = $this->fields();

        foreach ($this->descriptionFields as $name) {
            /** @psalm-var mixed $candidate */
            $candidate = $fields[$name] ?? [];

            /** @psalm-var array<string, mixed> $safe */
            $safe = is_array($candidate)
                ? $candidate
                : [];
            $text = trim((new AdfText($safe))->text());

            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    #[Override]
    public function raw(): array
    {
        return $this->payload;
    }

    /**
     * Extracts fields sub-array from payload.
     *
     * @return array<string, mixed>
     */
    private function fields(): array
    {
        /** @psalm-var mixed $fields */
        $fields = $this->payload['fields'] ?? [];

        /** @psalm-var array<string, mixed> $result */
        $result = is_array($fields)
            ? $fields
            : [];

        return $result;
    }
}
