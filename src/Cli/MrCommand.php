<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;
use Goblin\MergeRequest\DraftTitle;
use Goblin\MergeRequest\GitLabMergeRequest;
use JsonException;
use Override;

/**
 * Dispatches merge request subcommands to GitLab API.
 */
final readonly class MrCommand implements Command
{
    /**
     * Stores GitLab merge request client.
     */
    public function __construct(private GitLabMergeRequest $mr) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $sub = $args->positional(0);

        $data = match ($sub) {
            'create' => $this->created($args),
            'view' => $this->mr->view((int) $args->positional(1)),
            'list' => $this->mr->list([
                'state' => $args->option('state'),
                'source_branch' => $args->option('source'),
                'target_branch' => $args->option('target'),
                'search' => $args->option('search'),
            ]),
            'update' => $this->updated($args),
            default => throw new GoblinException("Unknown mr subcommand: {$sub}"),
        };

        try {
            echo json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . PHP_EOL;
        } catch (JsonException $e) {
            throw new GoblinException("Failed to encode JSON: {$e->getMessage()}");
        }

        return 0;
    }

    /**
     * Creates a merge request from CLI arguments.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    private function created(Arguments $args): array
    {
        $title = $args->option('title');

        return $this->mr->create([
            'source_branch' => $args->option('source'),
            'target_branch' => $args->option('target'),
            'title' => $args->flag('draft')
                ? (new DraftTitle($title))->drafted()
                : $title,
            'description' => $args->option('description'),
        ]);
    }

    /**
     * Updates a merge request with draft/ready title handling.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    private function updated(Arguments $args): array
    {
        $iid = (int) $args->positional(1);
        $changes = array_filter(
            [
                'title' => $args->option('title'),
                'target_branch' => $args->option('target'),
                'description' => $args->option('description'),
            ],
            static fn(string $v): bool => $v !== '',
        );

        if ($args->flag('draft') || $args->flag('ready')) {
            $title = $this->titleForDraft($args->option('title'), $iid);
            $changes['title'] = $args->flag('draft')
                ? (new DraftTitle($title))->drafted()
                : (new DraftTitle($title))->ready();
        }

        return $this->mr->update($iid, $changes);
    }

    /**
     * Returns explicit title or fetches current from API.
     *
     * @throws GoblinException
     */
    private function titleForDraft(string $explicit, int $iid): string
    {
        if ($explicit !== '') {
            return $explicit;
        }

        $viewed = $this->mr->view($iid);

        return array_key_exists('title', $viewed) && is_string($viewed['title'])
            ? $viewed['title']
            : '';
    }
}
