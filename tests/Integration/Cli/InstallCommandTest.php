<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\InstallCommand;
use Goblin\Tests\Constraint\InstalledHooks;
use Goblin\Tests\Constraint\SkippedHooks;
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
            (new InstallCommand($output))->run(new Arguments([], []));
        });

        self::assertThat($output, new InstalledHooks());
    }

    #[Test]
    public function skipsExistingHooks(): void
    {
        $output = new FakeOutput();

        (new WithHooksBackup())->run(function () use ($output): void {
            (new InstallCommand(new FakeOutput()))->run(new Arguments([], []));
            (new InstallCommand($output))->run(new Arguments([], []));
        });

        self::assertThat($output, new SkippedHooks());
    }

    #[Test]
    public function returnsZeroExitCode(): void
    {
        $code = (new WithHooksBackup())->run(
            fn(): int => (new InstallCommand(new FakeOutput()))
                ->run(new Arguments([], [])),
        );

        self::assertSame(0, $code, 'must return 0 on success');
    }
}
