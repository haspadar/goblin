<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;
use Override;

/**
 * Dispatches to a named command from a registry.
 *
 * @psalm-api
 */
final readonly class RoutedCommand implements Command
{
    /**
     * Stores available commands by name.
     *
     * @param array<string, Command> $commands
     */
    public function __construct(private array $commands) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $name = $args->command();

        if ($name === '' || !array_key_exists($name, $this->commands)) {
            throw new GoblinException("Unknown command: {$name}");
        }

        return $this->commands[$name]->run($args);
    }
}
