<?php

declare(strict_types=1);

namespace Goblin\Issue;

/**
 * Renders Jira comment payloads into structured arrays.
 */
final readonly class JiraComments
{
    /**
     * Stores raw comment entries.
     *
     * @param list<mixed> $comments
     */
    public function __construct(private array $comments) {}

    /**
     * Returns comments with ADF bodies rendered to plain text.
     *
     * @return list<array<string, mixed>>
     */
    public function rendered(): array
    {
        $result = [];

        /** @psalm-var mixed $comment */
        foreach ($this->comments as $comment) {
            if (!is_array($comment)) {
                continue;
            }

            /** @phpstan-var array<string, mixed> $comment */
            $result[] = $this->renderOne($comment);
        }

        return $result;
    }

    /**
     * Renders a single comment entry.
     *
     * @param array<string, mixed> $comment
     * @return array<string, mixed>
     */
    private function renderOne(array $comment): array
    {
        /** @psalm-var mixed $author */
        $author = $comment['author'] ?? [];

        /** @psalm-var mixed $body */
        $body = $comment['body'] ?? [];

        /** @psalm-var array<string, mixed> $safeBody */
        $safeBody = is_array($body)
            ? $body
            : [];

        return [
            'id' => $comment['id'] ?? '',
            'author' => is_array($author)
                ? ($author['displayName'] ?? '')
                : '',
            'created_at' => $comment['created'] ?? '',
            'updated_at' => $comment['updated'] ?? '',
            'body' => trim((new AdfText($safeBody))->text()),
        ];
    }
}
