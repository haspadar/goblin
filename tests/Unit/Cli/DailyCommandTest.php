<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\DailyCommand;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DailyCommandTest extends TestCase
{
    #[Test]
    public function outputsDailyReport(): void
    {
        $cmd = new DailyCommand(
            $this->httpWithActivity(),
            new FakeConfig(['jira-url' => 'https://test.atlassian.net']),
        );

        ob_start();
        $cmd->run(new Arguments('daily', [], []));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'DAILY-7',
            $output,
            'daily command must output issue keys',
        );
    }

    #[Test]
    public function filtersReportByProject(): void
    {
        $prefix = 'project = "TEAM" AND ';
        $responses = [];

        $lastJql = $prefix . 'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay()';
        $responses[$this->searchUrl($lastJql)] = [
            'issues' => [['key' => 'TEAM-5']],
        ];

        $inProgressJql = $prefix
            . 'assignee = currentUser() AND status = "In Progress"';
        $responses[$this->searchUrl($inProgressJql)] = ['issues' => []];

        $queueJql = $prefix . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses[$this->searchUrl($queueJql)] = ['issues' => []];

        $cmd = new DailyCommand(
            new FakeHttp($responses),
            new FakeConfig([
                'jira-url' => 'https://test.atlassian.net',
                'project' => 'TEAM',
            ]),
        );

        ob_start();
        $cmd->run(new Arguments('daily', [], []));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'TEAM-5',
            $output,
            'daily command must filter by project from config',
        );
    }

    private function httpWithActivity(): FakeHttp
    {
        $responses = [];

        $lastJql = 'status CHANGED BY currentUser() '
            . 'AFTER startOfDay(-1d) BEFORE startOfDay()';
        $responses[$this->searchUrl($lastJql)] = [
            'issues' => [['key' => 'DAILY-7']],
        ];

        $inProgressJql = 'assignee = currentUser() AND status = "In Progress"';
        $responses[$this->searchUrl($inProgressJql)] = ['issues' => []];

        $queueJql = 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';
        $responses[$this->searchUrl($queueJql)] = ['issues' => []];

        return new FakeHttp($responses);
    }

    private function searchUrl(string $jql): string
    {
        $query = http_build_query([
            'jql' => $jql,
            'fields' => 'key',
            'maxResults' => 50,
        ]);

        return "GET /rest/api/3/search/jql?{$query}";
    }
}
