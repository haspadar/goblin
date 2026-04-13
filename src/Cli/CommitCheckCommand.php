<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\Git\CommitCheck;
use Goblin\Git\CommitMessage;
use Goblin\Git\Git;
use Goblin\GoblinException;
use Goblin\Output\Output;
use Override;

/**
 * Validates commit message against branch issue key.
 */
final readonly class CommitCheckCommand implements Command
{
    /**
     * Stores git, configuration, and output channel.
     */
    public function __construct(private Git $git, private Config $config, private Output $output) {}

    #[Override]
    public function run(Arguments $args): int
    {
        try {
            /** @var non-empty-string $regex */
            $regex = $this->config->value('project-regex');

            (new CommitCheck(
                $this->git->currentBranch(),
                (new CommitMessage($args->positional(0)))->text(),
                $regex,
            ))->validate();
        } catch (GoblinException $e) {
            $this->output->error($e->getMessage());

            return 1;
        }

        $this->output->success('Commit is valid');

        return 0;
    }
}
