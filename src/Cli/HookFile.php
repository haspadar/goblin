<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;

/**
 * Git hook file with idempotent install-or-prepend semantics.
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
        if (!file_exists($this->path) || filesize($this->path) === 0) {
            return $this->write(self::SHEBANG . "\n\n" . $this->block, HookAction::Installed);
        }

        $current = file_get_contents($this->path);

        if ($current === false) {
            throw new GoblinException("Failed to read hook: {$this->path}");
        }

        if (preg_match('/^' . preg_quote(self::MARKER, '/') . '\b/m', $current) === 1) {
            return HookAction::Skipped;
        }

        return $this->write($this->prepend($current), HookAction::Prepended);
    }

    /**
     * Inserts the goblin block between the shebang and the foreign body.
     * Foreign bytes are preserved verbatim — no line-ending normalization, no trimming.
     */
    private function prepend(string $current): string
    {
        if (!str_starts_with($current, '#!')) {
            return self::SHEBANG . "\n\n" . $this->block . "\n" . $current;
        }

        $newline = strpos($current, "\n");

        if ($newline === false) {
            return $current . "\n\n" . $this->block;
        }

        $shebang = substr($current, 0, $newline + 1);
        $rest = substr($current, $newline + 1);

        return $shebang . "\n" . $this->block . "\n" . $rest;
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

        if (!chmod($this->path, 0o755)) {
            throw new GoblinException("Failed to make hook executable: {$this->path}");
        }

        return $action;
    }
}
