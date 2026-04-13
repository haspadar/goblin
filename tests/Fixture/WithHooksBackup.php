<?php

declare(strict_types=1);

namespace Goblin\Tests\Fixture;

use Goblin\Cli\InstallHook;

/**
 * Backs up git hooks, runs a closure, then restores originals.
 */
final class WithHooksBackup
{
    /** @var list<string> */
    private readonly array $hooks;

    public function __construct()
    {
        $this->hooks = array_map(
            static fn(InstallHook $hook): string => $hook->value,
            InstallHook::cases(),
        );
    }

    /**
     * Backs up hooks, executes the callback, restores hooks.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     */
    public function run(\Closure $callback): mixed
    {
        $dir = $this->hooksDir();
        $saved = $this->backup($dir);

        try {
            return $callback();
        } finally {
            $this->restore($dir, $saved);
        }
    }

    private function hooksDir(): string
    {
        exec('git rev-parse --show-toplevel', $lines, $code);

        if ($code !== 0 || $lines === []) {
            throw new \RuntimeException('Not a git repository');
        }

        return $lines[0] . '/.git/hooks';
    }

    /**
     * @return array<string, string>
     */
    private function backup(string $dir): array
    {
        $saved = [];

        foreach ($this->hooks as $hook) {
            $path = $dir . '/' . $hook;

            if (file_exists($path)) {
                $saved[$hook] = (string) file_get_contents($path);
                unlink($path);
            }
        }

        return $saved;
    }

    /**
     * @param array<string, string> $saved
     */
    private function restore(string $dir, array $saved): void
    {
        foreach ($this->hooks as $hook) {
            $path = $dir . '/' . $hook;

            if (file_exists($path) && !array_key_exists($hook, $saved)) {
                unlink($path);
            }

            if (array_key_exists($hook, $saved)) {
                file_put_contents($path, $saved[$hook]);
                chmod($path, 0o755);
            }
        }
    }
}
