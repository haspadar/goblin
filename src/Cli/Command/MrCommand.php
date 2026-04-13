<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Git\Git;
use Goblin\GoblinException;
use Goblin\Http\Http;
use Goblin\MergeRequest\DraftTitle;
use Goblin\MergeRequest\GitLabMergeRequest;
use Goblin\MergeRequest\ProjectPath;
use JsonException;
use Override;

/**
 * Dispatches merge request subcommands to GitLab API.
 */
final readonly class MrCommand implements Command
{
    /**
     * Stores git state and GitLab HTTP client.
     */
    public function __construct(private Git $git, private Http $http) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $mr = new GitLabMergeRequest(
            $this->http,
            (new ProjectPath($this->git->remote()))->value(),
        );
        $sub = $args->positional(0);

        $data = match ($sub) {
            'create' => $this->created($mr, $args),
            'view' => $mr->view($this->iid($args)),
            'list' => $mr->list([
                'state' => $args->option('state'),
                'source_branch' => $args->option('source'),
                'target_branch' => $args->option('target'),
                'search' => $args->option('search'),
            ]),
            'update' => $this->updated($mr, $args),
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
    private function created(GitLabMergeRequest $mr, Arguments $args): array
    {
        $source = $args->option('source');
        $target = $args->option('target');
        $title = $args->option('title');

        if ($source === '' || $target === '' || $title === '') {
            throw new GoblinException('Options --source, --target and --title are required');
        }

        return $mr->create([
            'source_branch' => $source,
            'target_branch' => $target,
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
    private function updated(GitLabMergeRequest $mr, Arguments $args): array
    {
        $iid = $this->iid($args);
        $changes = array_filter(
            [
                'title' => $args->option('title'),
                'target_branch' => $args->option('target'),
                'description' => $args->option('description'),
            ],
            static fn(string $v): bool => $v !== '',
        );

        if ($args->flag('draft') && $args->flag('ready')) {
            throw new GoblinException('Options --draft and --ready are mutually exclusive');
        }

        if ($args->flag('draft') || $args->flag('ready')) {
            $explicit = $args->option('title');
            $viewed = $mr->view($iid);
            $current = array_key_exists('title', $viewed) && is_string($viewed['title'])
                ? $viewed['title']
                : '';
            $title = $explicit !== ''
                ? $explicit
                : $current;
            $changes['title'] = $args->flag('draft')
                ? (new DraftTitle($title))->drafted()
                : (new DraftTitle($title))->ready();
        }

        return $mr->update($iid, $changes);
    }

    /**
     * Extracts and validates IID from positional argument.
     *
     * @throws GoblinException
     */
    private function iid(Arguments $args): int
    {
        $raw = $args->positional(1);

        if ($raw === '' || !ctype_digit($raw)) {
            throw new GoblinException('Merge request IID is required');
        }

        return (int) $raw;
    }
}
