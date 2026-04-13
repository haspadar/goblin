<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\InstallCommand;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InstallCommandTest extends TestCase
{
    #[Test]
    public function installsThreeHooks(): void
    {
        $backups = $this->backupHooks();
        $output = new FakeOutput();

        (new InstallCommand($output))->run(new Arguments('install', [], []));
        $this->restoreHooks($backups);

        self::assertCount(
            3,
            $output->successes,
            'must install commit-msg, pre-push, and post-checkout',
        );
    }

    #[Test]
    public function skipsExistingHooks(): void
    {
        $backups = $this->backupHooks();

        (new InstallCommand(new FakeOutput()))->run(new Arguments('install', [], []));

        $output = new FakeOutput();
        (new InstallCommand($output))->run(new Arguments('install', [], []));
        $this->restoreHooks($backups);

        self::assertCount(
            3,
            $output->muted,
            'must skip all hooks when they already exist',
        );
    }

    #[Test]
    public function returnsZeroExitCode(): void
    {
        $backups = $this->backupHooks();

        $code = (new InstallCommand(new FakeOutput()))
            ->run(new Arguments('install', [], []));
        $this->restoreHooks($backups);

        self::assertSame(0, $code, 'must return 0 on success');
    }

    /**
     * @return array<string, string>
     */
    private function backupHooks(): array
    {
        $dir = $this->hooksDir();
        $backups = [];

        foreach (['commit-msg', 'pre-push', 'post-checkout'] as $hook) {
            $path = $dir . '/' . $hook;

            if (file_exists($path)) {
                $backups[$hook] = (string) file_get_contents($path);
                unlink($path);
            }
        }

        return $backups;
    }

    /**
     * @param array<string, string> $backups
     */
    private function restoreHooks(array $backups): void
    {
        $dir = $this->hooksDir();

        foreach (['commit-msg', 'pre-push', 'post-checkout'] as $hook) {
            $path = $dir . '/' . $hook;

            if (file_exists($path) && !array_key_exists($hook, $backups)) {
                unlink($path);
            }

            if (array_key_exists($hook, $backups)) {
                file_put_contents($path, $backups[$hook]);
                chmod($path, 0o755);
            }
        }
    }

    private function hooksDir(): string
    {
        exec('git rev-parse --show-toplevel', $lines);

        return $lines[0] . '/.git/hooks';
    }
}
