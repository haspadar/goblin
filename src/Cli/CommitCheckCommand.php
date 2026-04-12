<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\Git\CommitCheck;
use Override;

/**
 * Validates commit message against branch issue key.
 */
final readonly class CommitCheckCommand implements Command
{
    /**
     * Stores configuration for project regex.
     */
    public function __construct(private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        /** @var non-empty-string $regex */
        $regex = $this->config->value('project-regex');

        (new CommitCheck(
            $args->positional(0),
            $args->positional(1),
            $regex,
        ))->validate();

        return 0;
    }
}
