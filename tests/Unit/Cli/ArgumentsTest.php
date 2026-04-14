<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArgumentsTest extends TestCase
{
    #[Test]
    public function returnsOptionValue(): void
    {
        $args = new Arguments(['title' => 'Dark mode'], []);

        self::assertSame(
            'Dark mode',
            $args->option('title'),
            'option must return its stored value',
        );
    }

    #[Test]
    public function returnsEmptyStringForMissingOption(): void
    {
        $args = new Arguments([], []);

        self::assertSame(
            '',
            $args->option('title'),
            'absent option must return empty string',
        );
    }

    #[Test]
    public function detectsFlagPresence(): void
    {
        $args = new Arguments(['draft' => true], []);

        self::assertTrue(
            $args->flag('draft'),
            'flag must be detected as present',
        );
    }

    #[Test]
    public function returnsFalseForMissingFlag(): void
    {
        $args = new Arguments([], []);

        self::assertFalse(
            $args->flag('draft'),
            'absent flag must return false',
        );
    }

    #[Test]
    public function returnsPositionalByIndex(): void
    {
        $args = new Arguments([], ['PROJ-99', 'description']);

        self::assertSame(
            'PROJ-99',
            $args->positional(0),
            'positional must be accessible by index',
        );
    }

    #[Test]
    public function returnsEmptyStringForMissingPositional(): void
    {
        $args = new Arguments([], []);

        self::assertSame(
            '',
            $args->positional(0),
            'absent positional must return empty string',
        );
    }
}
