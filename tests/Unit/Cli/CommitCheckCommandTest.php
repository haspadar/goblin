<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\CommitCheckCommand;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CommitCheckCommandTest extends TestCase
{
    #[Test]
    public function returnsZeroForMatchingKeys(): void
    {
        $cmd = new CommitCheckCommand(
            new FakeGit('PROJ-42-feature'),
            new FakeConfig(['project-regex' => '/[A-Z]+-\d+/']),
            new FakeOutput(),
        );

        self::assertSame(
            0,
            $cmd->run(new Arguments('commit-check', [], ['PROJ-42 Fix login bug'])),
            'matching keys must pass validation',
        );
    }

    #[Test]
    public function returnsOneForMismatchedKeys(): void
    {
        $output = new FakeOutput();
        $cmd = new CommitCheckCommand(
            new FakeGit('PROJ-42-feature'),
            new FakeConfig(['project-regex' => '/[A-Z]+-\d+/']),
            $output,
        );

        self::assertSame(
            1,
            $cmd->run(new Arguments('commit-check', [], ['OTHER-99 Wrong key'])),
            'mismatched keys must return exit code 1',
        );
    }

    #[Test]
    public function outputsSuccessMessage(): void
    {
        $output = new FakeOutput();
        $cmd = new CommitCheckCommand(
            new FakeGit('ACME-10-deploy'),
            new FakeConfig(['project-regex' => '/[A-Z]+-\d+/']),
            $output,
        );

        $cmd->run(new Arguments('commit-check', [], ['ACME-10 Ship release']));

        self::assertSame(
            'Commit is valid',
            $output->successes[0] ?? '',
            'must output success message on valid commit',
        );
    }

    #[Test]
    public function outputsErrorMessageOnFailure(): void
    {
        $output = new FakeOutput();
        $cmd = new CommitCheckCommand(
            new FakeGit('SHOP-77-cart'),
            new FakeConfig(['project-regex' => '/[A-Z]+-\d+/']),
            $output,
        );

        $cmd->run(new Arguments('commit-check', [], ['BILL-22 Update invoice']));

        self::assertStringContainsString(
            'differs from commit message',
            $output->errors[0] ?? '',
            'must output error message on invalid commit',
        );
    }
}
