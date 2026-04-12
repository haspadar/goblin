<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;
use Goblin\Output\Output;
use Override;

/**
 * Installs git hooks into the local repository.
 */
final readonly class InstallCommand implements Command
{
    /**
     * Stores output channel.
     */
    public function __construct(private Output $output) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $hooksDir = $this->hooksDir();
        $hooks = $this->hooks();

        foreach ($hooks as $name => $content) {
            $path = $hooksDir . '/' . $name;

            if (file_exists($path)) {
                $this->output->muted("Skipped {$name} (already exists)");

                continue;
            }

            file_put_contents($path, $content);
            chmod($path, 0o755);
            $this->output->success("Installed {$name}");
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
     * Returns hook scripts keyed by filename.
     *
     * @return array<string, string>
     */
    private function hooks(): array
    {
        $goblin = 'php "$ROOT/bin/goblin"';

        return [
            'commit-msg' => implode("\n", [
                '#!/bin/sh',
                'set -e',
                'ROOT="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0',
                "{$goblin} branch-check",
                "{$goblin} commit-check",
                '',
            ]),
            'pre-push' => implode("\n", [
                '#!/bin/sh',
                'ROOT="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0',
                "exec {$goblin} test --parallel",
                '',
            ]),
            'post-checkout' => implode("\n", [
                '#!/bin/sh',
                '[ "$3" != "1" ] && exit 0',
                'ROOT="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0',
                "exec {$goblin} branch-check",
                '',
            ]),
        ];
    }
}
