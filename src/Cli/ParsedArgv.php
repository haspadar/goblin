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
    /**
     * Stores raw argv array.
     *
     * @param list<string> $argv
     */
    public function __construct(private array $argv) {}

    /**
     * Returns parsed arguments.
     */
    public function arguments(): Arguments
    {
        $command = $this->argv[1] ?? '';
        $options = [];
        $positionals = [];

        $parsingOptions = true;

        foreach (array_slice($this->argv, 2) as $arg) {
            if ($parsingOptions && $arg === '--') {
                $parsingOptions = false;

                continue;
            }

            if ($parsingOptions && str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $options[$parts[0]] = $parts[1] ?? true;
            } else {
                $positionals[] = $arg;
            }
        }

        return new Arguments($command, $options, $positionals);
    }
}
