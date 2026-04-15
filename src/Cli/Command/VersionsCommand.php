<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Issue\VersionsList;
use Goblin\Output\Output;
use Override;

/**
 * Displays unreleased project versions with target branches.
 */
final readonly class VersionsCommand implements Command
{
    /**
     * Stores versions list and output.
     */
    public function __construct(private VersionsList $versions, private Output $output) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $pairs = $this->versions->pairs();
        $verbose = $args->flag('verbose');

        if ($verbose) {
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
