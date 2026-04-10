<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\PaginatedComments;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaginatedCommentsTest extends TestCase
{
    #[Test]
    public function returnsSinglePageComments(): void
    {
        $comments = new PaginatedComments(
            new FakeHttp([
                'GET /rest/api/3/issue/FX-10/comment?startAt=0&maxResults=100' => [
                    'comments' => [
                        ['id' => '1', 'body' => ['type' => 'doc']],
                        ['id' => '2', 'body' => ['type' => 'doc']],
                    ],
                    'total' => 2,
                    'maxResults' => 100,
                ],
            ]),
            'FX-10',
        );

        self::assertCount(
            2,
            $comments->all(),
            'single page must return all comments',
        );
    }

    #[Test]
    public function returnsEmptyForNoComments(): void
    {
        $comments = new PaginatedComments(
            new FakeHttp([
                'GET /rest/api/3/issue/FX-20/comment?startAt=0&maxResults=100' => [
                    'comments' => [],
                    'total' => 0,
                    'maxResults' => 100,
                ],
            ]),
            'FX-20',
        );

        self::assertSame(
            [],
            $comments->all(),
            'no comments must return empty array',
        );
    }

    #[Test]
    public function skipsNonArrayEntries(): void
    {
        $comments = new PaginatedComments(
            new FakeHttp([
                'GET /rest/api/3/issue/FX-30/comment?startAt=0&maxResults=100' => [
                    'comments' => [
                        'invalid',
                        ['id' => '5', 'body' => ['type' => 'doc']],
                        42,
                    ],
                    'total' => 3,
                    'maxResults' => 100,
                ],
            ]),
            'FX-30',
        );

        self::assertCount(
            1,
            $comments->all(),
            'non-array entries must be skipped',
        );
    }
}
