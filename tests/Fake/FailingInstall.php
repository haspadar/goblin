<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\InstallCommand;
use Goblin\GoblinException;

/**
 * Runs InstallCommand with given arguments and reports whether it failed.
 */
final class FailingInstall
{
    public function __construct(private readonly InstallCommand $command) {}

    /**
     * @param array<string, string> $options
     */
    public function failed(array $options): bool
    {
        try {
            $this->command->run(new Arguments($options, []));

            return false;
        } catch (GoblinException) {
            return true;
        }
    }
}
