<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\Config\Config;
use Goblin\Git\BranchCheck;
use Goblin\Git\Git;
use Goblin\Http\Http;
use Override;

/**
 * Validates current branch against Jira Fix Version.
 */
final readonly class BranchCheckCommand implements Command
{
    /**
     * Stores git, HTTP client, and configuration.
     */
    public function __construct(private Git $git, private Http $http, private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        (new BranchCheck($this->git, $this->http, $this->config))
            ->validate();

        return 0;
    }
}
