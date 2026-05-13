<?php

declare(strict_types=1);

namespace Goblin\Daily;

use Goblin\GoblinException;

/**
 * Generates daily activity report from Jira.
 */
final readonly class DailyReport
{
    private const int LOOKBACK_DAYS = 7;

    /**
     * Stores search client, project filter, and Jira base URL.
     *
     * @param JiraSearch $search Jira search adapter.
     * @param string $jiraUrl Jira base URL.
     * @param string $project Project identifier.
     */
    public function __construct(
        private JiraSearch $search,
        private string $jiraUrl,
        private string $project = '',
    ) {}

    /**
     * Returns formatted daily report text.
     *
     * @throws GoblinException
     */
    public function text(): string
    {
        $candidates = [
            $this->lastActivity(),
            $this->inProgress(),
            $this->queue(),
        ];

        $texts = [];
        $allKeys = [];

        foreach ($candidates as $block) {
            if ($block['text'] === '') {
                continue;
            }

            $texts[] = $block['text'];
            $allKeys = array_merge($allKeys, $block['keys']);
        }

        if ($texts === []) {
            throw new GoblinException(
                'Jira did not return any data. Check project key and permissions',
            );
        }

        $output = implode("\n", $texts);
        $unique = array_unique($allKeys);

        if ($unique !== []) {
            $links = array_map(
                fn(string $key): string => sprintf('%s/browse/%s', rtrim($this->jiraUrl, '/'), $key),
                $unique,
            );
            $output = sprintf("%s\n\nСсылки:\n%s", $output, implode("\n", $links));
        }

        return $output;
    }

    /**
     * Finds last active day within 7 days.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}
     */
    private function lastActivity(): array
    {
        $i = 1;

        while ($i <= self::LOOKBACK_DAYS) {
            $before = $i === 1
                ? 'startOfDay()'
                : sprintf('startOfDay(-%dd)', $i - 1);
            $jql = sprintf(
                '%sstatus CHANGED BY currentUser() AFTER startOfDay(-%dd) BEFORE %s',
                $this->projectJql(),
                $i,
                $before,
            );

            $keys = $this->search->keys($jql);

            if ($keys !== []) {
                return [
                    'text' => sprintf('%s: %s', (new DayLabel($i))->text(), implode(', ', $keys)),
                    'keys' => $keys,
                ];
            }

            $i++;
        }

        return ['text' => '', 'keys' => []];
    }

    /**
     * Finds issues currently in progress.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}
     */
    private function inProgress(): array
    {
        $jql = sprintf('%sassignee = currentUser() AND status = "In Progress"', $this->projectJql());

        $keys = $this->search->keys($jql);

        return $keys === [] ? ['text' => '', 'keys' => []] : [
            'text' => sprintf('Делаю: %s', implode(', ', $keys)),
            'keys' => $keys,
        ];
    }

    /**
     * Finds queued sprint issues.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}
     */
    private function queue(): array
    {
        $jql = sprintf(
            '%ssprint in openSprints() AND assignee = currentUser() AND status != Backlog AND status NOT IN ("In Progress", Done, Closed, Cancelled)',
            $this->projectJql(),
        );

        $keys = $this->search->keys($jql);

        return $keys === [] ? ['text' => '', 'keys' => []] : [
            'text' => sprintf('В очереди: %s', implode(', ', $keys)),
            'keys' => $keys,
        ];
    }

    /**
     * Returns project JQL prefix.
     */
    private function projectJql(): string
    {
        if ($this->project === '') {
            return '';
        }

        $escaped = addcslashes($this->project, '"\\');

        return "project = \"{$escaped}\" AND ";
    }
}
