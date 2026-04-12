<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\TestCommand;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeDocker;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TestCommandTest extends TestCase
{
    #[Test]
    public function runsTestsInContainer(): void
    {
        $docker = new FakeDocker(running: true, exitCode: 0);
        $output = new FakeOutput();
        $cmd = new TestCommand(
            $docker,
            new FakeConfig(['container' => 'webapp']),
            $output,
        );

        $code = $cmd->run(new Arguments('test', [], []));

        self::assertSame(0, $code, 'must return 0 when tests pass');
    }

    #[Test]
    public function runsParallelWhenFlagSet(): void
    {
        $docker = new FakeDocker(running: true, exitCode: 0);
        $cmd = new TestCommand(
            $docker,
            new FakeConfig(['container' => 'api-server']),
            new FakeOutput(),
        );

        $cmd->run(new Arguments('test', ['parallel' => true], []));

        self::assertSame(
            'php artisan test --parallel',
            $docker->lastCommand(),
            'must pass --parallel flag to artisan',
        );
    }

    #[Test]
    public function returnsOneOnTestFailure(): void
    {
        $output = new FakeOutput();
        $cmd = new TestCommand(
            new FakeDocker(running: true, exitCode: 2),
            new FakeConfig(['container' => 'billing']),
            $output,
        );

        $code = $cmd->run(new Arguments('test', [], []));

        self::assertSame(1, $code, 'must return 1 when tests fail');
    }

    #[Test]
    public function skipsWhenContainerNotRunning(): void
    {
        $output = new FakeOutput();
        $cmd = new TestCommand(
            new FakeDocker(running: false),
            new FakeConfig(['container' => 'workers']),
            $output,
        );

        $code = $cmd->run(new Arguments('test', [], []));

        self::assertSame(0, $code, 'must return 0 when container is not running');
    }

    #[Test]
    public function outputsSkipMessageWhenContainerDown(): void
    {
        $output = new FakeOutput();
        $cmd = new TestCommand(
            new FakeDocker(running: false),
            new FakeConfig(['container' => 'scheduler']),
            $output,
        );

        $cmd->run(new Arguments('test', [], []));

        self::assertStringContainsString(
            'scheduler',
            $output->muted[0] ?? '',
            'skip message must mention container name',
        );
    }

    #[Test]
    public function outputsSuccessMessageOnPass(): void
    {
        $output = new FakeOutput();
        $cmd = new TestCommand(
            new FakeDocker(running: true, exitCode: 0),
            new FakeConfig(['container' => 'mailer']),
            $output,
        );

        $cmd->run(new Arguments('test', [], []));

        self::assertSame(
            'Tests passed.',
            $output->successes[0] ?? '',
            'must output success message when tests pass',
        );
    }

    #[Test]
    public function outputsErrorMessageOnFailure(): void
    {
        $output = new FakeOutput();
        $cmd = new TestCommand(
            new FakeDocker(running: true, exitCode: 1),
            new FakeConfig(['container' => 'payments']),
            $output,
        );

        $cmd->run(new Arguments('test', [], []));

        self::assertSame(
            'Tests failed.',
            $output->errors[0] ?? '',
            'must output error message when tests fail',
        );
    }

    #[Test]
    public function usesContainerFromConfig(): void
    {
        $docker = new FakeDocker(running: true, exitCode: 0);
        $cmd = new TestCommand(
            $docker,
            new FakeConfig(['container' => 'custom-runner']),
            new FakeOutput(),
        );

        $cmd->run(new Arguments('test', [], []));

        self::assertSame(
            'custom-runner',
            $docker->lastContainer(),
            'must use container name from config',
        );
    }
}
