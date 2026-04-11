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

        self::assertStringContainsString(
            'PLT-10',
            $report->text(),
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

        self::assertStringContainsString(
            'Делаю: CRS-5',
            $report->text(),
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

        self::assertStringContainsString(
            'В очереди: OPS-7',
            $report->text(),
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

        self::assertStringContainsString(
            'https://jira.example.com/browse/CRS-5',
            $report->text(),
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
        $report = new DailyReport(
            new JiraSearch(new FakeHttp($this->allProjectResponses())),
            'https://jira.example.com',
        );

        self::assertStringContainsString(
            'DEV-1',
            $report->text(),
            'report without project must still return results',
        );
    }

    private function httpWithActivity(): FakeHttp
    {
        $responses = $this->emptySearchResponses('PLT');
        $activityJql = 'project = "PLT" AND '
            . 'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay()';
        $responses[$this->searchUrl($activityJql)] = [
            'issues' => [['key' => 'PLT-10']],
        ];

        return new FakeHttp($responses);
    }

    private function httpWithInProgress(): FakeHttp
    {
        $responses = $this->emptySearchResponses('CRS');
        $jql = 'project = "CRS" AND '
            . 'assignee = currentUser() AND status = "In Progress"';
        $responses[$this->searchUrl($jql)] = [
            'issues' => [['key' => 'CRS-5']],
        ];

        return new FakeHttp($responses);
    }

    private function httpWithQueue(): FakeHttp
    {
        $responses = $this->emptySearchResponses('OPS');
        $jql = 'project = "OPS" AND '
            . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses[$this->searchUrl($jql)] = [
            'issues' => [['key' => 'OPS-7']],
        ];

        return new FakeHttp($responses);
    }

    private function httpEmpty(): FakeHttp
    {
        return new FakeHttp($this->emptySearchResponses('EMPTY'));
    }

    private function searchUrl(string $jql): string
    {
        return 'GET /rest/api/3/search/jql?' . http_build_query([
            'jql' => $jql,
            'fields' => 'key',
            'maxResults' => 50,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allProjectResponses(): array
    {
        $responses = [];

        $responses[$this->searchUrl(
            'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay()',
        )] = ['issues' => [['key' => 'DEV-1']]];

        $responses[$this->searchUrl(
            'assignee = currentUser() AND status = "In Progress"',
        )] = ['issues' => []];

        $responses[$this->searchUrl(
            'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)',
        )] = ['issues' => []];

        return $responses;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptySearchResponses(string $project): array
    {
        $responses = [];

        for ($i = 1; $i <= 7; $i++) {
            $before = $i === 1 ? 'startOfDay()' : 'startOfDay(-' . ($i - 1) . 'd)';
            $jql = "project = \"{$project}\" AND "
                . 'status CHANGED BY currentUser() '
                . "AFTER startOfDay(-{$i}d) BEFORE {$before}";
            $responses[$this->searchUrl($jql)] = ['issues' => []];
        }

        $responses[$this->searchUrl(
            "project = \"{$project}\" AND "
            . 'assignee = currentUser() AND status = "In Progress"',
        )] = ['issues' => []];

        $responses[$this->searchUrl(
            "project = \"{$project}\" AND "
            . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)',
        )] = ['issues' => []];

        return $responses;
    }
}
