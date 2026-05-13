<?php

declare(strict_types=1);

namespace Goblin\Cli;

use Goblin\GoblinException;

/**
 * Git hook file with idempotent install-or-prepend semantics.
 */
final readonly class HookFile
{
    private const string MARKER = '# BEGIN goblin';

    private const string SHEBANG = '#!/bin/sh';

    private const int EXECUTABLE_MODE = 0o755;

    /**
     * Stores the target path and the shell block bounded by MARKER.
     *
     * @param string $path Filesystem path.
     * @param string $block Hook block content.
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
            return $this->write(sprintf("%s\n\n%s", self::SHEBANG, $this->block), HookAction::Installed);
        }

        $current = file_get_contents($this->path);

        if (!is_string($current)) {
            throw new GoblinException("Failed to read hook: {$this->path}");
        }

        if (preg_match(sprintf('/^%s\b/m', preg_quote(self::MARKER, '/')), $current) === 1) {
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
            return sprintf("%s\n\n%s\n%s", self::SHEBANG, $this->block, $current);
        }

        $newline = strpos($current, "\n");

        if (!is_int($newline)) {
            return sprintf("%s\n\n%s", $current, $this->block);
        }

        $shebang = substr($current, 0, $newline + 1);
        $rest = substr($current, $newline + 1);

        return sprintf("%s\n%s\n%s", $shebang, $this->block, $rest);
    }

    /**
     * Persists the new contents and flags the file executable.
     *
     * @throws GoblinException
     */
    private function write(string $contents, HookAction $action): HookAction
    {
        if (!is_int(file_put_contents($this->path, $contents))) {
            throw new GoblinException("Failed to write hook: {$this->path}");
        }

        if (!chmod($this->path, self::EXECUTABLE_MODE)) {
            throw new GoblinException("Failed to make hook executable: {$this->path}");
        }

        return $action;
    }
}
