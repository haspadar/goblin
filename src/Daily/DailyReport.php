<?php

declare(strict_types=1);

namespace Goblin\Daily;

use Goblin\GoblinException;

/**
 * Generates daily activity report from Jira.
 */
final readonly class DailyReport
{
    /**
     * Stores search client, project filter, and Jira base URL.
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
        $blocks = array_filter([
            $this->lastActivity(),
            $this->inProgress(),
            $this->queue(),
        ]);

        if ($blocks === []) {
            throw new GoblinException(
                'Jira did not return any data. Check project key and permissions',
            );
        }

        $texts = [];
        $allKeys = [];

        foreach ($blocks as $block) {
            $texts[] = $block['text'];
            $allKeys = array_merge($allKeys, $block['keys']);
        }

        $output = implode("\n", $texts);
        $unique = array_unique($allKeys);

        if ($unique !== []) {
            $links = array_map(
                fn(string $key): string => rtrim($this->jiraUrl, '/') . '/browse/' . $key,
                $unique,
            );
            $output .= "\n\nСсылки:\n" . implode("\n", $links);
        }

        return $output;
    }

    /**
     * Finds last active day within 7 days.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}|null
     */
    private function lastActivity(): ?array
    {
        $i = 1;

        while ($i <= 7) {
            $before = $i === 1
                ? 'startOfDay()'
                : 'startOfDay(-' . ($i - 1) . 'd)';
            $jql = $this->projectJql()
                . 'status CHANGED BY currentUser() '
                . "AFTER startOfDay(-{$i}d) BEFORE {$before}";

            $keys = $this->search->keys($jql);

            if ($keys !== []) {
                return [
                    'text' => (new DayLabel($i))->text() . ': ' . implode(', ', $keys),
                    'keys' => $keys,
                ];
            }

            $i++;
        }

        return null;
    }

    /**
     * Finds issues currently in progress.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}|null
     */
    private function inProgress(): ?array
    {
        $jql = $this->projectJql()
            . 'assignee = currentUser() AND status = "In Progress"';

        $keys = $this->search->keys($jql);

        return $keys === [] ? null : [
            'text' => 'Делаю: ' . implode(', ', $keys),
            'keys' => $keys,
        ];
    }

    /**
     * Finds queued sprint issues.
     *
     * @throws GoblinException
     * @return array{text: string, keys: list<string>}|null
     */
    private function queue(): ?array
    {
        $jql = $this->projectJql()
            . 'sprint in openSprints() '
            . 'AND assignee = currentUser() '
            . 'AND status != Backlog '
            . 'AND status NOT IN ("In Progress", Done, Closed, Cancelled)';

        $keys = $this->search->keys($jql);

        return $keys === [] ? null : [
            'text' => 'В очереди: ' . implode(', ', $keys),
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
