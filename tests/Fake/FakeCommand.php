<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\Command;
use LogicException;
use Override;

/**
 * Records invocations for test assertions.
 */
final class FakeCommand implements Command
{
    private ?Arguments $lastArgs = null;

    public function __construct(private readonly int $exitCode) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $this->lastArgs = $args;

        return $this->exitCode;
    }

    public function lastArgs(): Arguments
    {
        if ($this->lastArgs === null) {
            throw new LogicException('lastArgs() called before run()');
        }

        return $this->lastArgs;
    }
}
