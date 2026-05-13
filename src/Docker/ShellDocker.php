<?php

declare(strict_types=1);

namespace Goblin\Docker;

use Override;

/**
 * Docker operations via local shell commands.
 */
final readonly class ShellDocker implements Docker
{
    #[Override]
    public function isRunning(string $container): bool
    {
        exec(
            sprintf("docker ps --format '{{.Names}}' | grep -qw %s", escapeshellarg($container)),
            $lines,
            $code,
        );

        return $code === 0;
    }

    #[Override]
    public function exec(string $container, string $command): int
    {
        passthru(
            sprintf('docker exec %s %s 2>&1', escapeshellarg($container), $command),
            $code,
        );

        return $code;
    }
}
