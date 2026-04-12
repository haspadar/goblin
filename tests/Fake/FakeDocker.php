<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Docker\Docker;
use Override;

/**
 * In-memory Docker for tests.
 */
final class FakeDocker implements Docker
{
    private string $lastContainer = '';

    private string $lastCommand = '';

    /**
     * Configures fake behavior.
     *
     * @param bool $running Whether containers report as running
     * @param int $exitCode Exit code returned by exec
     */
    public function __construct(
        private readonly bool $running = true,
        private readonly int $exitCode = 0,
    ) {}

    #[Override]
    public function isRunning(string $container): bool
    {
        $this->lastContainer = $container;

        return $this->running;
    }

    #[Override]
    public function exec(string $container, string $command): int
    {
        $this->lastContainer = $container;
        $this->lastCommand = $command;

        return $this->exitCode;
    }

    public function lastContainer(): string
    {
        return $this->lastContainer;
    }

    public function lastCommand(): string
    {
        return $this->lastCommand;
    }
}
