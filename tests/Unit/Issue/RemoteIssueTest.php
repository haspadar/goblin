<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\DescriptionFields;
use Goblin\Issue\IssueKey;
use Goblin\Issue\RemoteIssue;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RemoteIssueTest extends TestCase
{
    #[Test]
    public function returnsDetailsWithComments(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/issue/PROJ-42' => [
                'key' => 'PROJ-42',
                'fields' => [
                    'summary' => 'Fix broken pipeline',
                    'description' => [
                        'type' => 'doc',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Pipeline fails on deploy'],
                                ],
                            ],
                        ],
                    ],
                    'comment' => ['comments' => []],
                ],
            ],
            'GET /rest/api/3/issue/PROJ-42/comment?startAt=0&maxResults=100' => [
                'comments' => [
                    [
                        'id' => '30001',
                        'author' => ['displayName' => 'Igor Volkov'],
                        'created' => '2026-03-10T09:00:00.000+0000',
                        'updated' => '2026-03-10T09:00:00.000+0000',
                        'body' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'Confirmed on staging'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'total' => 1,
                'maxResults' => 100,
            ],
            'GET /rest/api/3/field' => [],
        ]);

        $issue = new RemoteIssue(
            $http,
            new IssueKey('PROJ-42'),
            new DescriptionFields($http),
        );

        $details = $issue->details();

        self::assertSame(
            'PROJ-42',
            $details['key'],
            'details must include issue key from API',
        );
    }

    #[Test]
    public function returnsCommentBodyAsPlainText(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/issue/PROJ-42' => [
                'key' => 'PROJ-42',
                'fields' => [
                    'summary' => 'Fix broken pipeline',
                    'description' => [],
                    'comment' => ['comments' => []],
                ],
            ],
            'GET /rest/api/3/issue/PROJ-42/comment?startAt=0&maxResults=100' => [
                'comments' => [
                    [
                        'id' => '30001',
                        'author' => ['displayName' => 'Igor Volkov'],
                        'created' => '2026-03-10T09:00:00.000+0000',
                        'updated' => '2026-03-10T09:00:00.000+0000',
                        'body' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'Confirmed on staging'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'total' => 1,
                'maxResults' => 100,
            ],
            'GET /rest/api/3/field' => [],
        ]);

        $issue = new RemoteIssue(
            $http,
            new IssueKey('PROJ-42'),
            new DescriptionFields($http),
        );

        $details = $issue->details();

        self::assertSame(
            'Confirmed on staging',
            $details['comments'][0]['body'],
            'comment body must be rendered from ADF to plain text',
        );
    }

    #[Test]
    public function returnsDescriptionFromPayload(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/issue/TASK-7' => [
                'key' => 'TASK-7',
                'fields' => [
                    'summary' => 'Add logging',
                    'description' => [
                        'type' => 'doc',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Add structured logging to API layer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'GET /rest/api/3/field' => [],
        ]);

        $issue = new RemoteIssue(
            $http,
            new IssueKey('TASK-7'),
            new DescriptionFields($http),
        );

        self::assertSame(
            'Add structured logging to API layer',
            $issue->description(),
            'description must be extracted from ADF payload',
        );
    }

    #[Test]
    public function returnsRawPayload(): void
    {
        $payload = [
            'key' => 'OPS-15',
            'fields' => ['summary' => 'Deploy hotfix'],
        ];

        $http = new FakeHttp([
            'GET /rest/api/3/issue/OPS-15' => $payload,
            'GET /rest/api/3/field' => [],
        ]);

        $issue = new RemoteIssue(
            $http,
            new IssueKey('OPS-15'),
            new DescriptionFields($http),
        );

        self::assertSame(
            $payload,
            $issue->raw(),
            'raw must return original API payload',
        );
    }

    #[Test]
    public function normalizesNumericKey(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/issue/ACME-123' => [
                'key' => 'ACME-123',
                'fields' => ['summary' => 'Numeric key test'],
            ],
            'GET /rest/api/3/field' => [],
        ]);

        $issue = new RemoteIssue(
            $http,
            new IssueKey('123', 'acme'),
            new DescriptionFields($http),
        );

        self::assertSame(
            'ACME-123',
            $issue->raw()['key'],
            'numeric input must resolve to PROJECT-NUMBER format',
        );
    }
}
