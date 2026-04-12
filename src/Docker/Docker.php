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
     */
    public function isRunning(string $container): bool;

    /**
     * Runs a command inside a container and returns exit code.
     */
    public function exec(string $container, string $command): int;
}
