<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Shell\Shell;
use Override;

/**
 * In-memory shell for tests: records the last command and returns a canned exit code.
 */
final class FakeShell implements Shell
{
    private string $lastCommand = '';

    public function __construct(private readonly int $exitCode = 0) {}

    #[Override]
    public function run(string $command): int
    {
        $this->lastCommand = $command;

        return $this->exitCode;
    }

    public function lastCommand(): string
    {
        return $this->lastCommand;
    }
}
