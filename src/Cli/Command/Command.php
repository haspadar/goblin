<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\GoblinException;

/**
 * Executable CLI command.
 *
 * @psalm-api
 */
interface Command
{
    /**
     * Runs the command and returns exit code.
     *
     * @throws GoblinException
     */
    public function run(Arguments $args): int;
}
