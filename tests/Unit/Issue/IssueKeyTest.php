<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\GoblinException;
use Goblin\Issue\IssueKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueKeyTest extends TestCase
{
    #[Test]
    public function returnsUppercasedAlphanumericKey(): void
    {
        self::assertSame(
            'PROJ-42',
            (new IssueKey('proj-42'))->value(),
            'alphabetic key must be uppercased',
        );
    }

    #[Test]
    public function prependsProjectToNumericInput(): void
    {
        self::assertSame(
            'BEAM-718',
            (new IssueKey('718', 'BEAM'))->value(),
            'numeric input must be prefixed with project',
        );
    }

    #[Test]
    public function uppercasesProjectPrefixForNumericInput(): void
    {
        self::assertSame(
            'CORE-305',
            (new IssueKey('305', 'core'))->value(),
            'lowercase project prefix must be uppercased',
        );
    }

    #[Test]
    public function treatsAlphanumericInputAsKey(): void
    {
        self::assertSame(
            '718A',
            (new IssueKey('718a'))->value(),
            'input with letters must not be treated as numeric',
        );
    }

    #[Test]
    public function throwsWhenProjectIsWhitespaceOnly(): void
    {
        $this->expectException(GoblinException::class);

        (new IssueKey('42', '   '))->value();
    }

    #[Test]
    public function throwsWhenNumericInputWithoutProject(): void
    {
        $this->expectException(GoblinException::class);

        (new IssueKey('99'))->value();
    }

    #[Test]
    public function throwsWhenInputIsEmpty(): void
    {
        $this->expectException(GoblinException::class);

        (new IssueKey(''))->value();
    }

    #[Test]
    public function throwsWhenInputIsWhitespaceOnly(): void
    {
        $this->expectException(GoblinException::class);

        (new IssueKey('   '))->value();
    }
}
