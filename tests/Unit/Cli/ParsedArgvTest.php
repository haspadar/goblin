<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\ParsedArgv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParsedArgvTest extends TestCase
{
    #[Test]
    public function parsesCommandFromArgv(): void
    {
        $parsed = new ParsedArgv(['bin/goblin', 'issue', 'ABC-42']);

        self::assertSame(
            'issue',
            $parsed->command(),
            'second argv element must become command',
        );
    }

    #[Test]
    public function parsesOptionWithValue(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'mr', '--title=Dark mode']))->arguments();

        self::assertSame(
            'Dark mode',
            $args->option('title'),
            'option after = must be captured as value',
        );
    }

    #[Test]
    public function parsesBareFlag(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'mr', '--draft']))->arguments();

        self::assertTrue(
            $args->flag('draft'),
            'bare --flag must register as present',
        );
    }

    #[Test]
    public function parsesPositionals(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'issue', 'PROJ-7', 'raw']))->arguments();

        self::assertSame(
            'PROJ-7',
            $args->positional(0),
            'non-option args must become positionals',
        );
    }

    #[Test]
    public function returnsEmptyCommandForMinimalArgv(): void
    {
        $parsed = new ParsedArgv(['bin/goblin']);

        self::assertSame(
            '',
            $parsed->command(),
            'missing command must yield empty string',
        );
    }

    #[Test]
    public function preservesEqualsSignInOptionValue(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'mr', '--query=a=b']))->arguments();

        self::assertSame(
            'a=b',
            $args->option('query'),
            'equals inside value must not split further',
        );
    }

    #[Test]
    public function bareFlagOptionReturnsEmptyString(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'mr', '--draft']))->arguments();

        self::assertSame(
            '',
            $args->option('draft'),
            'bare flag via option() must return empty string',
        );
    }

    #[Test]
    public function treatsTokensAfterTerminatorAsPositionals(): void
    {
        $args = (new ParsedArgv(['bin/goblin', 'issue', '--', '--raw']))->arguments();

        self::assertSame(
            '--raw',
            $args->positional(0),
            'tokens after -- must become positionals',
        );
    }
}
