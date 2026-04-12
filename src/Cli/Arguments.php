<?php

declare(strict_types=1);

namespace Goblin\Cli;

/**
 * Parsed CLI arguments.
 *
 * @psalm-api
 */
final readonly class Arguments
{
    /**
     * Stores parsed components.
     *
     * @param array<string, string|true> $options
     * @param list<string> $positionals
     */
    public function __construct(
        private string $command,
        private array $options,
        private array $positionals,
    ) {}

    /**
     * Returns the subcommand name.
     */
    public function command(): string
    {
        return $this->command;
    }

    /**
     * Returns an option value by key, or empty string if absent.
     */
    public function option(string $key): string
    {
        $value = $this->options[$key] ?? '';

        return is_string($value)
            ? $value
            : '';
    }

    /**
     * Returns a positional argument by index, or empty string.
     */
    public function positional(int $index): string
    {
        return $this->positionals[$index] ?? '';
    }

    /**
     * Checks whether a flag option is present.
     */
    public function flag(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }
}
