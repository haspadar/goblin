<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\LocalTestCommand;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeOutput;
use Goblin\Tests\Fake\FakeShell;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocalTestCommandTest extends TestCase
{
    #[Test]
    public function runsTestCommandFromConfig(): void
    {
        $shell = new FakeShell(exitCode: 0);
        $cmd = new LocalTestCommand(
            $shell,
            new FakeConfig(['test-command' => 'vendor/bin/phpunit --testsuite=fast']),
            new FakeOutput(),
        );

        $cmd->run(new Arguments([], []));

        self::assertSame(
            'vendor/bin/phpunit --testsuite=fast',
            $shell->lastCommand(),
            'must run the command from config verbatim',
        );
    }

    #[Test]
    public function defaultsToArtisanTestWhenConfigOmitsKey(): void
    {
        $shell = new FakeShell(exitCode: 0);
        $cmd = new LocalTestCommand(
            $shell,
            new FakeConfig([]),
            new FakeOutput(),
        );

        $cmd->run(new Arguments([], []));

        self::assertSame(
            'php artisan test',
            $shell->lastCommand(),
            'must fall back to php artisan test when test-command absent',
        );
    }

    #[Test]
    public function returnsZeroOnSuccess(): void
    {
        $cmd = new LocalTestCommand(
            new FakeShell(exitCode: 0),
            new FakeConfig(['test-command' => 'vendor/bin/pest']),
            new FakeOutput(),
        );

        $code = $cmd->run(new Arguments([], []));

        self::assertSame(0, $code, 'must return 0 when shell exits 0');
    }

    #[Test]
    public function returnsOneOnFailure(): void
    {
        $cmd = new LocalTestCommand(
            new FakeShell(exitCode: 7),
            new FakeConfig(['test-command' => 'make test']),
            new FakeOutput(),
        );

        $code = $cmd->run(new Arguments([], []));

        self::assertSame(1, $code, 'must return 1 when shell exits non-zero');
    }

    #[Test]
    public function reportsFailureMessage(): void
    {
        $output = new FakeOutput();
        $cmd = new LocalTestCommand(
            new FakeShell(exitCode: 1),
            new FakeConfig(['test-command' => 'composer run-script test']),
            $output,
        );

        $cmd->run(new Arguments([], []));

        self::assertSame(
            'Tests failed.',
            $output->errors[0] ?? '',
            'must emit failure message on non-zero exit',
        );
    }

    #[Test]
    public function reportsSuccessMessage(): void
    {
        $output = new FakeOutput();
        $cmd = new LocalTestCommand(
            new FakeShell(exitCode: 0),
            new FakeConfig(['test-command' => 'go test ./...']),
            $output,
        );

        $cmd->run(new Arguments([], []));

        self::assertSame(
            'Tests passed.',
            $output->successes[0] ?? '',
            'must emit success message on zero exit',
        );
    }
}
