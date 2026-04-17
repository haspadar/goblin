<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Cli\InstallHook;
use Goblin\Config\Config;
use Goblin\Docker\ComposeContainer;
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

    private const string GOBLIN_LINE = 'if [ -f "$ROOT/bin/branch-check" ]; then GOBLIN="$ROOT"; elif [ -f "$ROOT/../goblin/bin/branch-check" ]; then GOBLIN="$ROOT/../goblin"; else echo "Goblin binaries not found in $ROOT/bin or $ROOT/../goblin/bin" >&2; exit 1; fi';

    private const string DEFAULT_SERVICE = 'app';

    /**
     * Stores output channel and configuration.
     */
    public function __construct(private Output $output, private Config $config) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $root = $this->root();
        $fromFlag = $args->option('container');
        $container = $fromFlag !== ''
            ? $fromFlag
            : $this->resolveContainer($root, $args);
        $hooksDir = $root . '/.git/hooks';

        if (!is_dir($hooksDir)) {
            throw new GoblinException("Hooks directory not found: {$hooksDir}");
        }

        foreach (InstallHook::cases() as $hook) {
            $path = $hooksDir . '/' . $hook->value;

            if (file_exists($path)) {
                $this->output->muted("Skipped {$hook->value} (already exists)");

                continue;
            }

            if (file_put_contents($path, $this->script($hook, $container)) === false) {
                throw new GoblinException("Failed to write hook: {$hook->value}");
            }

            chmod($path, 0o755);
            $this->output->success("Installed {$hook->value}");
        }

        return 0;
    }

    /**
     * Returns the git repository root.
     *
     * @throws GoblinException
     */
    private function root(): string
    {
        exec('git rev-parse --show-toplevel', $lines, $code);
        $root = trim(implode("\n", $lines));

        if ($code !== 0 || $root === '') {
            throw new GoblinException('Not a git repository');
        }

        return $root;
    }

    /**
     * Resolves container name from docker-compose.yml using the configured service key.
     *
     * @throws GoblinException
     */
    private function resolveContainer(string $root, Arguments $args): string
    {
        return (new ComposeContainer($root, $this->service($args)))->name();
    }

    /**
     * Returns the compose service key (flag wins, then config, then default).
     *
     * @throws GoblinException
     */
    private function service(Arguments $args): string
    {
        $fromFlag = $args->option('service');

        if ($fromFlag !== '') {
            return $fromFlag;
        }

        return $this->config->has('compose-service')
            ? $this->config->value('compose-service')
            : self::DEFAULT_SERVICE;
    }

    /**
     * Returns the shell script for a given hook.
     */
    private function script(InstallHook $hook, string $container): string
    {
        return match ($hook) {
            InstallHook::CommitMsg => implode("\n", [
                self::SHEBANG,
                'set -e',
                self::ROOT_LINE,
                self::GOBLIN_LINE,
                'php "$GOBLIN/bin/branch-check"',
                'php "$GOBLIN/bin/commit-check" "$1"',
                '',
            ]),
            InstallHook::PrePush => implode("\n", [
                self::SHEBANG,
                self::ROOT_LINE,
                self::GOBLIN_LINE,
                'exec php "$GOBLIN/bin/docker-test" --parallel --container=' . escapeshellarg($container),
                '',
            ]),
            InstallHook::PostCheckout => implode("\n", [
                self::SHEBANG,
                '[ "$3" != "1" ] && exit 0',
                self::ROOT_LINE,
                self::GOBLIN_LINE,
                'exec php "$GOBLIN/bin/branch-check"',
                '',
            ]),
        };
    }
}
