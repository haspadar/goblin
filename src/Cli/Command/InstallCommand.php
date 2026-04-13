<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Cli\InstallHook;
use Goblin\GoblinException;
use Goblin\Output\Output;
use Override;

/**
 * Installs git hooks into the local repository.
 */
final readonly class InstallCommand implements Command
{
    private const string SHEBANG = '#!/bin/sh';

    private const string ROOT_LINE = 'ROOT="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0';

    /**
     * Stores output channel.
     */
    public function __construct(private Output $output) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $hooksDir = $this->hooksDir();

        foreach (InstallHook::cases() as $hook) {
            $path = $hooksDir . '/' . $hook->value;

            if (file_exists($path)) {
                $this->output->muted("Skipped {$hook->value} (already exists)");

                continue;
            }

            if (file_put_contents($path, $this->script($hook)) === false) {
                throw new GoblinException("Failed to write hook: {$hook->value}");
            }

            chmod($path, 0o755);
            $this->output->success("Installed {$hook->value}");
        }

        return 0;
    }

    /**
     * Returns the path to .git/hooks directory.
     *
     * @throws GoblinException
     */
    private function hooksDir(): string
    {
        exec('git rev-parse --show-toplevel', $lines, $code);
        $root = trim(implode("\n", $lines));

        if ($code !== 0 || $root === '') {
            throw new GoblinException('Not a git repository');
        }

        $dir = $root . '/.git/hooks';

        if (!is_dir($dir)) {
            throw new GoblinException("Hooks directory not found: {$dir}");
        }

        return $dir;
    }

    /**
     * Returns the shell script for a given hook.
     */
    private function script(InstallHook $hook): string
    {
        $goblin = 'php "$ROOT/bin/goblin"';

        return match ($hook) {
            InstallHook::CommitMsg => implode("\n", [
                self::SHEBANG,
                'set -e',
                self::ROOT_LINE,
                "{$goblin} branch-check",
                "{$goblin} commit-check \"\$1\"",
                '',
            ]),
            InstallHook::PrePush => implode("\n", [
                self::SHEBANG,
                self::ROOT_LINE,
                "exec {$goblin} test --parallel",
                '',
            ]),
            InstallHook::PostCheckout => implode("\n", [
                self::SHEBANG,
                '[ "$3" != "1" ] && exit 0',
                self::ROOT_LINE,
                "exec {$goblin} branch-check",
                '',
            ]),
        };
    }
}
