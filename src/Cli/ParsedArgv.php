<?php

declare(strict_types=1);

namespace Goblin\Cli;

/**
 * Parses raw argv into Arguments.
 *
 * @psalm-api
 */
final readonly class ParsedArgv
{
    private const int COMMAND_OFFSET = 2;

    private const int OPTION_PREFIX_LENGTH = 2;

    private const int OPTION_SPLIT_LIMIT = 2;

    /**
     * Stores raw argv array.
     *
     * @param list<string> $argv Process arguments.
     */
    public function __construct(private array $argv) {}

    /**
     * Returns the command name (first non-option argument).
     */
    public function command(): string
    {
        return $this->argv[1] ?? '';
    }

    /**
     * Returns parsed arguments (without the command).
     */
    public function arguments(): Arguments
    {
        $options = [];
        $positionals = [];

        $parsingOptions = true;

        foreach (array_slice($this->argv, self::COMMAND_OFFSET) as $arg) {
            if ($parsingOptions && $arg === '--') {
                $parsingOptions = false;

                continue;
            }

            if ($parsingOptions && str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, self::OPTION_PREFIX_LENGTH), self::OPTION_SPLIT_LIMIT);
                $options[$parts[0]] = $parts[1] ?? true;
            } else {
                $positionals[] = $arg;
            }
        }

        return new Arguments($options, $positionals);
    }
}
