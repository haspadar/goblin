<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Daily;

use Goblin\Daily\DailyReport;
use Goblin\Daily\JiraSearch;
use Goblin\GoblinException;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DailyReportTest extends TestCase
{
    #[Test]
    public function includesLastActivityAndLinks(): void
    {
        $report = new DailyReport(
            new JiraSearch($this->httpWithActivity()),
            'https://jira.example.com',
            'PLT',
        );

        $text = $report->text();

        self::assertStringContainsString(
            'PLT-10',
            $text,
            'report must include issue key from last activity',
        );
    }

    #[Test]
    public function includesInProgressSection(): void
    {
        $report = new DailyReport(
            new JiraSearch($this->httpWithInProgress()),
            'https://jira.example.com',
            'CRS',
        );

        $text = $report->text();

        self::assertStringContainsString(
            'Делаю: CRS-5',
            $text,
            'report must show in-progress issues',
        );
    }

    #[Test]
    public function includesQueueSection(): void
    {
        $report = new DailyReport(
            new JiraSearch($this->httpWithQueue()),
            'https://jira.example.com',
            'OPS',
        );

        $text = $report->text();

        self::assertStringContainsString(
            'В очереди: OPS-7',
            $text,
            'report must show queued issues',
        );
    }

    #[Test]
    public function appendsJiraLinks(): void
    {
        $report = new DailyReport(
            new JiraSearch($this->httpWithInProgress()),
            'https://jira.example.com',
            'CRS',
        );

        $text = $report->text();

        self::assertStringContainsString(
            'https://jira.example.com/browse/CRS-5',
            $text,
            'report must append clickable Jira links',
        );
    }

    #[Test]
    public function throwsWhenNoDataReturned(): void
    {
        $report = new DailyReport(
            new JiraSearch($this->httpEmpty()),
            'https://jira.example.com',
            'EMPTY',
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Jira did not return any data');

        $report->text();
    }

    #[Test]
    public function worksWithoutProjectFilter(): void
    {
        $http = new FakeHttp($this->allProjectResponses());

        $report = new DailyReport(
            new JiraSearch($http),
            'https://jira.example.com',
        );

        $text = $report->text();

        self::assertStringContainsString(
            'DEV-1',
            $text,
            'report without project must still return results',
        );
    }

    private function httpWithActivity(): FakeHttp
    {
        $responses = $this->emptySearchResponses('PLT');
        $activityJql = 'project = PLT AND '
            . 'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay(-0d)';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $activityJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => [['key' => 'PLT-10']]];

        return new FakeHttp($responses);
    }

    private function httpWithInProgress(): FakeHttp
    {
        $responses = $this->emptySearchResponses('CRS');
        $inProgressJql = 'project = CRS AND '
            . 'assignee = currentUser() AND status = "In Progress"';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $inProgressJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => [['key' => 'CRS-5']]];

        return new FakeHttp($responses);
    }

    private function httpWithQueue(): FakeHttp
    {
        $responses = $this->emptySearchResponses('OPS');
        $queueJql = 'project = OPS AND '
            . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $queueJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => [['key' => 'OPS-7']]];

        return new FakeHttp($responses);
    }

    private function httpEmpty(): FakeHttp
    {
        return new FakeHttp($this->emptySearchResponses('EMPTY'));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allProjectResponses(): array
    {
        $responses = [];

        $activityJql = 'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay(-0d)';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $activityJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => [['key' => 'DEV-1']]];

        $inProgressJql = 'assignee = currentUser() AND status = "In Progress"';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $inProgressJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => []];

        $queueJql = 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $queueJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => []];

        return $responses;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptySearchResponses(string $project): array
    {
        $responses = [];

        for ($i = 1; $i <= 7; $i++) {
            $jql = "project = {$project} AND "
                . 'status CHANGED BY currentUser() '
                . "AFTER startOfDay(-{$i}d) BEFORE startOfDay(-" . ($i - 1) . 'd)';
            $responses['GET /rest/api/3/search/jql?' . http_build_query([
                'jql' => $jql, 'fields' => 'key', 'maxResults' => 50,
            ])] = ['issues' => []];
        }

        $inProgressJql = "project = {$project} AND "
            . 'assignee = currentUser() AND status = "In Progress"';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $inProgressJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => []];

        $queueJql = "project = {$project} AND "
            . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses['GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $queueJql, 'fields' => 'key', 'maxResults' => 50,
        ])] = ['issues' => []];

        return $responses;
    }
}
