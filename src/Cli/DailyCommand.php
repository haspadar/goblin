<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\Daily\DailyReport;
use Goblin\Daily\JiraSearch;
use Goblin\GoblinException;
use Goblin\Http\Http;
use Override;

/**
 * Outputs daily activity report from Jira.
 */
final readonly class DailyCommand implements Command
{
    /**
     * Stores HTTP client and configuration.
     */
    public function __construct(private Http $http, private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $report = new DailyReport(
            new JiraSearch($this->http),
            $this->config->value('jira-url'),
            $this->project(),
        );

        echo $report->text() . PHP_EOL;

        return 0;
    }

    /**
     * Returns project prefix from config.
     *
     * @throws GoblinException
     */
    private function project(): string
    {
        return $this->config->has('project')
            ? $this->config->value('project')
            : '';
    }
}
