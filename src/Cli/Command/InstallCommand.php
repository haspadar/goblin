<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Cli\HookAction;
use Goblin\Cli\HookFile;
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
            $action = (new HookFile($hooksDir . '/' . $hook->value, $this->block($hook, $container)))->install();
            $this->report($hook, $action);
        }

        return 0;
    }

    /**
     * Reports the outcome of a single hook install to the output channel.
     */
    private function report(InstallHook $hook, HookAction $action): void
    {
        match ($action) {
            HookAction::Installed => $this->output->success("Installed {$hook->value}"),
            HookAction::Appended => $this->output->success("Appended goblin block to {$hook->value}"),
            HookAction::Skipped => $this->output->muted("Skipped {$hook->value} (already installed)"),
        };
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
     * Returns the self-contained shell block (with BEGIN/END markers) for a given hook.
     */
    private function block(InstallHook $hook, string $container): string
    {
        return match ($hook) {
            InstallHook::CommitMsg => $this->wrap([
                '( php "$GOBLIN/bin/branch-check" ) || exit $?',
                '( php "$GOBLIN/bin/commit-check" "$1" ) || exit $?',
            ]),
            InstallHook::PrePush => $this->wrap([
                'php "$GOBLIN/bin/docker-test" --parallel --container=' . escapeshellarg($container) . ' || exit $?',
            ]),
            InstallHook::PostCheckout => $this->wrap([
                'if [ "$3" = "1" ]; then',
                '    php "$GOBLIN/bin/branch-check" || exit $?',
                'fi',
            ]),
        };
    }

    /**
     * Wraps the given lines in the goblin marker block with GOBLIN resolution.
     *
     * @param list<string> $body
     */
    private function wrap(array $body): string
    {
        $lines = [
            HookFile::MARKER,
            'GOBLIN_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)" || exit 0',
            'if [ -f "$GOBLIN_ROOT/bin/branch-check" ]; then GOBLIN="$GOBLIN_ROOT";'
                . ' elif [ -f "$GOBLIN_ROOT/../goblin/bin/branch-check" ]; then GOBLIN="$GOBLIN_ROOT/../goblin";'
                . ' else echo "Goblin binaries not found in $GOBLIN_ROOT/bin or $GOBLIN_ROOT/../goblin/bin" >&2; exit 1; fi',
            ...$body,
            '# END goblin',
            '',
        ];

        return implode("\n", $lines);
    }
}
