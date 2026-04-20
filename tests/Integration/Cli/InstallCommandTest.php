<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\InstallCommand;
use Goblin\Cli\InstallHook;
use Goblin\Tests\Constraint\InstalledHooks;
use Goblin\Tests\Constraint\NoHookFiles;
use Goblin\Tests\Constraint\SkippedHooks;
use Goblin\Tests\Fake\FailingInstall;
use Goblin\Tests\Fake\FakeConfig;
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
            (new InstallCommand($output, new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));
        });

        self::assertThat($output, new InstalledHooks());
    }

    #[Test]
    public function skipsExistingHooks(): void
    {
        $output = new FakeOutput();

        (new WithHooksBackup())->run(function () use ($output): void {
            (new InstallCommand(new FakeOutput(), new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));
            (new InstallCommand($output, new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));
        });

        self::assertThat($output, new SkippedHooks());
    }

    #[Test]
    public function returnsZeroExitCode(): void
    {
        $code = (new WithHooksBackup())->run(
            fn(): int => (new InstallCommand(new FakeOutput(), new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], [])),
        );

        self::assertSame(0, $code, 'must return 0 on success');
    }

    #[Test]
    public function resolvesGoblinPathInEveryHook(): void
    {
        (new WithHooksBackup())->run(function (): void {
            (new InstallCommand(new FakeOutput(), new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));

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

    #[Test]
    public function appendsGoblinBlockToForeignHook(): void
    {
        $output = new FakeOutput();

        (new WithHooksBackup())->run(function () use ($output): void {
            exec('git rev-parse --show-toplevel', $lines, $code);
            self::assertSame(0, $code, 'git rev-parse must succeed');
            $foreign = "#!/bin/sh\necho foreign-commit-msg\n";
            file_put_contents($lines[0] . '/.git/hooks/commit-msg', $foreign);

            (new InstallCommand($output, new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));
        });

        self::assertContains(
            'Appended goblin block to commit-msg',
            $output->successes,
            'foreign hook must be reported as appended',
        );
    }

    #[Test]
    public function preservesForeignContentWhenAppending(): void
    {
        $foreign = "#!/bin/sh\necho sentinel-pre-push-line\n";

        (new WithHooksBackup())->run(function () use ($foreign): void {
            exec('git rev-parse --show-toplevel', $lines, $code);
            self::assertSame(0, $code, 'git rev-parse must succeed');
            file_put_contents($lines[0] . '/.git/hooks/pre-push', $foreign);

            (new InstallCommand(new FakeOutput(), new FakeConfig([])))
                ->run(new Arguments(['container' => 'goblin-test-app'], []));

            $after = (string) file_get_contents($lines[0] . '/.git/hooks/pre-push');

            self::assertStringStartsWith($foreign, $after, 'append must keep foreign content at the top');
        });
    }

    #[Test]
    public function writesContainerFlagIntoPrePushHook(): void
    {
        (new WithHooksBackup())->run(function (): void {
            (new InstallCommand(new FakeOutput(), new FakeConfig([])))
                ->run(new Arguments(['container' => 'checking-app'], []));

            exec('git rev-parse --show-toplevel', $lines, $code);
            self::assertSame(0, $code, 'git rev-parse must succeed');
            $prePush = (string) file_get_contents($lines[0] . '/.git/hooks/pre-push');

            self::assertStringContainsString(
                "--container='checking-app'",
                $prePush,
                'pre-push must embed the resolved container name',
            );
        });
    }

    #[Test]
    public function failsWhenContainerCannotBeResolved(): void
    {
        $failed = (new WithHooksBackup())->run(
            fn(): bool => (new FailingInstall(new InstallCommand(new FakeOutput(), new FakeConfig([]))))
                ->failed([]),
        );

        self::assertTrue($failed, 'must throw when --container is absent and compose is missing');
    }

    #[Test]
    public function writesNoHooksWhenContainerCannotBeResolved(): void
    {
        (new WithHooksBackup())->run(function (): void {
            (new FailingInstall(new InstallCommand(new FakeOutput(), new FakeConfig([]))))
                ->failed([]);
            exec('git rev-parse --show-toplevel', $lines, $code);
            self::assertSame(0, $code, 'git rev-parse must succeed');

            self::assertThat($lines[0] . '/.git/hooks', new NoHookFiles());
        });
    }
}
