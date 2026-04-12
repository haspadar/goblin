<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\GoblinException;
use Goblin\Http\Http;
use Goblin\Issue\DescriptionFields;
use Goblin\Issue\IssueKey;
use Goblin\Issue\RemoteIssue;
use JsonException;
use Override;

/**
 * Displays Jira issue details, description, or raw payload.
 */
final readonly class IssueCommand implements Command
{
    /**
     * Stores HTTP client and configuration.
     */
    public function __construct(private Http $http, private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $issue = new RemoteIssue(
            $this->http,
            new IssueKey(
                $args->positional(0),
                $this->project(),
            ),
            new DescriptionFields($this->http),
        );
        $mode = $args->positional(1);

        echo match ($mode) {
            'description' => $issue->description() . PHP_EOL,
            'raw' => $this->json($issue->raw()),
            default => $this->json($issue->details()),
        };

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

    /**
     * Encodes array as pretty JSON.
     *
     * @param array<string, mixed> $data
     * @throws GoblinException
     */
    private function json(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . PHP_EOL;
        } catch (JsonException $e) {
            throw new GoblinException("Failed to encode JSON: {$e->getMessage()}");
        }
    }
}
