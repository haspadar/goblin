<?php

declare(strict_types=1);

namespace Goblin\Output;

use Override;

/**
 * ANSI-colored output for interactive terminals.
 */
final readonly class TextOutput implements Output
{
    #[Override]
    public function info(string $text): void
    {
        fwrite(STDOUT, "\033[33m{$text}\033[0m" . PHP_EOL);
    }

    #[Override]
    public function success(string $text): void
    {
        fwrite(STDOUT, "\033[32m{$text}\033[0m" . PHP_EOL);
    }

    #[Override]
    public function error(string $text): void
    {
        fwrite(STDERR, "\033[31m{$text}\033[0m" . PHP_EOL);
    }

    #[Override]
    public function muted(string $text): void
    {
        fwrite(STDOUT, "\033[90m{$text}\033[0m" . PHP_EOL);
    }
}
