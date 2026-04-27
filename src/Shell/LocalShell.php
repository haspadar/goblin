<?php

declare(strict_types=1);

namespace Goblin\Shell;

use Override;

/**
 * Shell command executor on the local host.
 */
final readonly class LocalShell implements Shell
{
    #[Override]
    public function run(string $command): int
    {
        passthru($command . ' 2>&1', $code);

        return $code;
    }
}
