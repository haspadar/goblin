<?php

declare(strict_types=1);

namespace Goblin\Output;

use Override;

/**
 * Decorator that adds debug timestamps to stderr.
 */
final readonly class DebugOutput implements Output
{
    /**
     * Wraps an existing output with debug logging.
     */
    public function __construct(private Output $origin) {}

    #[Override]
    public function info(string $text): void
    {
        $this->debug('info', $text);
        $this->origin->info($text);
    }

    #[Override]
    public function success(string $text): void
    {
        $this->debug('success', $text);
        $this->origin->success($text);
    }

    #[Override]
    public function error(string $text): void
    {
        $this->debug('error', $text);
        $this->origin->error($text);
    }

    #[Override]
    public function muted(string $text): void
    {
        $this->debug('muted', $text);
        $this->origin->muted($text);
    }

    /**
     * Writes a debug line to stderr.
     */
    private function debug(string $level, string $text): void
    {
        fwrite(
            STDERR,
            '[' . date('H:i:s') . "] [{$level}] {$text}" . PHP_EOL,
        );
    }
}
