<?php

declare(strict_types=1);

namespace Goblin\Shell;

/**
 * Executes shell commands on the host.
 *
 * @psalm-api
 */
interface Shell
{
    /**
     * Runs a trusted command on the host and returns its exit code.
     *
     * @param string $command Shell command from a trusted source (not user input)
     */
    public function run(string $command): int;
}
