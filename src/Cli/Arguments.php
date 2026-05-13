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
     * @param array<string, string|true> $options Parsed options.
     * @param list<string> $positionals Parsed positional arguments.
     */
    public function __construct(private array $options, private array $positionals) {}

    /**
     * Returns an option value by key, or empty string if absent.
     *
     * @param string $key Option key.
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
     *
     * @param int $index Positional index.
     */
    public function positional(int $index): string
    {
        return $this->positionals[$index] ?? '';
    }

    /**
     * Checks whether a flag option is present.
     *
     * @param string $key Option key.
     */
    public function flag(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }
}
