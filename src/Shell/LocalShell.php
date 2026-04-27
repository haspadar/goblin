<?php

declare(strict_types=1);

namespace Goblin\Shell;

use Goblin\Output\Output;
use Override;

/**
 * Shell command executor on the local host.
 */
final readonly class LocalShell implements Shell
{
    /**
     * Stores output channel.
     */
    public function __construct(private Output $output) {}

    #[Override]
    public function run(string $command): int
    {
        exec($command . ' 2>&1', $lines, $code);
        $this->output->muted(implode("\n", $lines));

        return $code;
    }
}
