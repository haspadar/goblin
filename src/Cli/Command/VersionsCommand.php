<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Http\Http;
use Goblin\Issue\ProjectKey;
use Goblin\Issue\VersionsList;
use Goblin\Output\Output;
use Override;

/**
 * Displays unreleased project versions with target branches.
 */
final readonly class VersionsCommand implements Command
{
    /**
     * Stores HTTP client, project resolver, and output.
     */
    public function __construct(
        private Http $http,
        private ProjectKey $project,
        private Output $output,
    ) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $project = $this->project->value();
        $pairs = (new VersionsList($this->http, $project))->pairs();
        $verbose = $args->flag('verbose');

        if ($verbose) {
            $this->output->muted("Project: {$project}");
            $this->output->muted('Versions: ' . count($pairs));
        }

        foreach ($pairs as $pair) {
            $this->output->info("{$pair['version']} → {$pair['branch']}");
        }

        if ($verbose) {
            $this->output->success('Done');
        }

        return 0;
    }
}
