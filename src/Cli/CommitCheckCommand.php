<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\Git\CommitCheck;
use Goblin\Git\CommitMessage;
use Goblin\Git\Git;
use Override;

/**
 * Validates commit message against branch issue key.
 */
final readonly class CommitCheckCommand implements Command
{
    /**
     * Stores git and configuration.
     */
    public function __construct(private Git $git, private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        /** @var non-empty-string $regex */
        $regex = $this->config->value('project-regex');

        (new CommitCheck(
            $this->git->currentBranch(),
            (new CommitMessage($args->positional(0)))->text(),
            $regex,
        ))->validate();

        return 0;
    }
}
