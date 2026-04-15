<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\InstallCommand;
use Goblin\Cli\InstallHook;
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

    #[Test]
    public function resolvesGoblinPathInEveryHook(): void
    {
        (new WithHooksBackup())->run(function (): void {
            (new InstallCommand(new FakeOutput()))->run(new Arguments([], []));

            exec('git rev-parse --show-toplevel', $lines, $code);
            self::assertSame(0, $code, 'git rev-parse must succeed');
            $dir = $lines[0] . '/.git/hooks';

            foreach (InstallHook::cases() as $hook) {
                $content = (string) file_get_contents($dir . '/' . $hook->value);

                self::assertStringContainsString(
                    '$GOBLIN/bin/',
                    $content,
                    "{$hook->value} must reference goblin binaries via \$GOBLIN",
                );
            }
        });
    }
}
