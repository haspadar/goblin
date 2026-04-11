<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Daily;

use Goblin\Daily\JiraSearch;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JiraSearchTest extends TestCase
{
    #[Test]
    public function returnsIssueKeysFromResponse(): void
    {
        $search = new JiraSearch(
            new FakeHttp([
                'GET /rest/api/3/search/jql?jql=project+%3D+MSP&fields=key&maxResults=50' => [
                    'issues' => [
                        ['key' => 'MSP-1'],
                        ['key' => 'MSP-2'],
                    ],
                ],
            ]),
        );

        self::assertSame(
            ['MSP-1', 'MSP-2'],
            $search->keys('project = MSP'),
            'must extract key field from each issue',
        );
    }

    #[Test]
    public function returnsEmptyListWhenNoIssues(): void
    {
        $search = new JiraSearch(
            new FakeHttp([
                'GET /rest/api/3/search/jql?jql=project+%3D+QA&fields=key&maxResults=50' => [
                    'issues' => [],
                ],
            ]),
        );

        self::assertSame(
            [],
            $search->keys('project = QA'),
            'empty issues array must return empty list',
        );
    }

    #[Test]
    public function skipsEntriesWithoutStringKey(): void
    {
        $search = new JiraSearch(
            new FakeHttp([
                'GET /rest/api/3/search/jql?jql=status+%3D+Open&fields=key&maxResults=50' => [
                    'issues' => [
                        ['key' => 'OPS-3'],
                        ['id' => 999],
                        'not-an-array',
                    ],
                ],
            ]),
        );

        self::assertSame(
            ['OPS-3'],
            $search->keys('status = Open'),
            'entries without string key must be skipped',
        );
    }
}
