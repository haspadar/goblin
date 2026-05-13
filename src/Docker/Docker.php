<?php

declare(strict_types=1);

namespace Goblin\Docker;

/**
 * Executes commands inside Docker containers.
 *
 * @psalm-api
 */
interface Docker
{
    /**
     * Checks whether a container is currently running.
     *
     * @param string $container Container value.
     */
    public function isRunning(string $container): bool;

    /**
     * Runs a trusted command inside a container and returns exit code.
     *
     * @param string $command Shell command from a trusted source (not user input)
     * @param string $container Container value.
     */
    public function exec(string $container, string $command): int;
}
