<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\JiraIssue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JiraIssueTest extends TestCase
{
    #[Test]
    public function returnsStructuredDetails(): void
    {
        $issue = new JiraIssue(
            [
                'key' => 'GOBLIN-42',
                'fields' => [
                    'summary' => 'Fix login timeout',
                    'description' => [
                        'type' => 'doc',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Users cannot log in'],
                                ],
                            ],
                        ],
                    ],
                    'comment' => [
                        'comments' => [
                            [
                                'id' => '10001',
                                'author' => ['displayName' => 'Alexei Petrov'],
                                'created' => '2026-01-15T10:00:00.000+0000',
                                'updated' => '2026-01-15T10:05:00.000+0000',
                                'body' => [
                                    'type' => 'doc',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Reproduced on staging'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['description'],
        );

        $details = $issue->details();

        self::assertSame(
            'GOBLIN-42',
            $details['key'],
            'key must match payload',
        );
    }

    #[Test]
    public function returnsCommentAuthor(): void
    {
        $issue = new JiraIssue(
            [
                'key' => 'GOBLIN-77',
                'fields' => [
                    'summary' => 'Upgrade PHP',
                    'description' => [],
                    'comment' => [
                        'comments' => [
                            [
                                'id' => '20001',
                                'author' => ['displayName' => 'Marina Sokolova'],
                                'created' => '2026-02-20T14:30:00.000+0000',
                                'updated' => '2026-02-20T14:30:00.000+0000',
                                'body' => [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'Tested on PHP 8.4'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['description'],
        );

        $details = $issue->details();
        $comments = $details['comments'];

        self::assertSame(
            'Marina Sokolova',
            $comments[0]['author'],
            'comment author displayName must be extracted',
        );
    }

    #[Test]
    public function returnsDescriptionFromFirstNonEmptyField(): void
    {
        $issue = new JiraIssue(
            [
                'key' => 'GOBLIN-99',
                'fields' => [
                    'description' => [],
                    'customfield_11961' => [
                        'type' => 'doc',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Found in acceptance criteria'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['description', 'customfield_11961'],
        );

        self::assertSame(
            'Found in acceptance criteria',
            $issue->description(),
            'first non-empty ADF field must be used as description',
        );
    }

    #[Test]
    public function returnsEmptyDescriptionWhenAllFieldsEmpty(): void
    {
        $issue = new JiraIssue(
            [
                'key' => 'GOBLIN-50',
                'fields' => [
                    'description' => [],
                    'customfield_13466' => [],
                ],
            ],
            ['description', 'customfield_13466'],
        );

        self::assertSame(
            '',
            $issue->description(),
            'empty ADF fields must produce empty description',
        );
    }

    #[Test]
    public function returnsRawPayload(): void
    {
        $payload = [
            'key' => 'GOBLIN-33',
            'fields' => ['summary' => 'Raw test'],
        ];

        $issue = new JiraIssue($payload, ['description']);

        self::assertSame(
            $payload,
            $issue->raw(),
            'raw must return original payload',
        );
    }

    #[Test]
    public function handlesNonArrayComments(): void
    {
        $issue = new JiraIssue(
            [
                'key' => 'GOBLIN-61',
                'fields' => [
                    'summary' => 'Broken comments',
                    'description' => [],
                    'comment' => ['comments' => 'invalid'],
                ],
            ],
            ['description'],
        );

        $details = $issue->details();

        self::assertSame(
            [],
            $details['comments'],
            'non-array comments must produce empty list',
        );
    }

    #[Test]
    public function handlesNonArrayFields(): void
    {
        $issue = new JiraIssue(
            ['key' => 'GOBLIN-88', 'fields' => 'broken'],
            ['description'],
        );

        self::assertSame(
            '',
            $issue->description(),
            'non-array fields must produce empty description',
        );
    }
}
