<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\InstallCommand;
use Goblin\Tests\Fake\FakeOutput;
use Goblin\Tests\Fixture\WithHooksBackup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InstallCommandTest extends TestCase
{
    #[Test]
    public function installsThreeHooks(): void
    {
        $output = new FakeOutput();

        (new WithHooksBackup())->run(function () use ($output): void {
            (new InstallCommand($output))->run(new Arguments('install', [], []));
        });

        self::assertSame(
            ['Installed commit-msg', 'Installed pre-push', 'Installed post-checkout'],
            $output->successes,
            'must install all three hooks',
        );
    }

    #[Test]
    public function skipsExistingHooks(): void
    {
        $output = new FakeOutput();

        (new WithHooksBackup())->run(function () use ($output): void {
            (new InstallCommand(new FakeOutput()))->run(new Arguments('install', [], []));
            (new InstallCommand($output))->run(new Arguments('install', [], []));
        });

        self::assertSame(
            ['Skipped commit-msg (already exists)', 'Skipped pre-push (already exists)', 'Skipped post-checkout (already exists)'],
            $output->muted,
            'must skip all hooks when they already exist',
        );
    }

    #[Test]
    public function returnsZeroExitCode(): void
    {
        $code = 1;

        (new WithHooksBackup())->run(function () use (&$code): void {
            $code = (new InstallCommand(new FakeOutput()))
                ->run(new Arguments('install', [], []));
        });

        self::assertSame(0, $code, 'must return 0 on success');
    }
}
