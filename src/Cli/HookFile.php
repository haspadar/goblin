<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;

/**
 * Git hook file with idempotent install-or-append semantics.
 */
final readonly class HookFile
{
    public const string MARKER = '# BEGIN goblin';

    private const string SHEBANG = '#!/bin/sh';

    /**
     * Stores the target path and the shell block bounded by MARKER.
     */
    public function __construct(private string $path, private string $block) {}

    /**
     * Writes the block, returning which transition happened.
     *
     * @throws GoblinException
     */
    public function install(): HookAction
    {
        if (!file_exists($this->path)) {
            return $this->write(self::SHEBANG . "\n\n" . $this->block, HookAction::Installed);
        }

        $current = (string) file_get_contents($this->path);

        if (str_contains($current, self::MARKER)) {
            return HookAction::Skipped;
        }

        return $this->write(rtrim($current, "\n") . "\n\n" . $this->block, HookAction::Appended);
    }

    /**
     * Persists the new contents and flags the file executable.
     *
     * @throws GoblinException
     */
    private function write(string $contents, HookAction $action): HookAction
    {
        if (file_put_contents($this->path, $contents) === false) {
            throw new GoblinException("Failed to write hook: {$this->path}");
        }

        chmod($this->path, 0o755);

        return $action;
    }
}
