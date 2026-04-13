<?php

declare(strict_types=1);

namespace Goblin\Output;

use Override;

/**
 * Decorator that prepends a prefix to every message.
 */
final readonly class PrefixedOutput implements Output
{
    /**
     * Wraps an existing output with a text prefix.
     */
    public function __construct(private string $prefix, private Output $origin) {}

    #[Override]
    public function info(string $text): void
    {
        $this->origin->info($this->prefix . $text);
    }

    #[Override]
    public function success(string $text): void
    {
        $this->origin->success($this->prefix . $text);
    }

    #[Override]
    public function error(string $text): void
    {
        $this->origin->error($this->prefix . $text);
    }

    #[Override]
    public function muted(string $text): void
    {
        $this->origin->muted($this->prefix . $text);
    }
}
