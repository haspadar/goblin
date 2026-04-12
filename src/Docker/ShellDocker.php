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
            "docker ps --format '{{.Names}}' | grep -qw " . escapeshellarg($container),
            $lines,
            $code,
        );

        return $code === 0;
    }

    #[Override]
    public function exec(string $container, string $command): int
    {
        passthru(
            'docker exec ' . escapeshellarg($container) . ' ' . $command,
            $code,
        );

        return $code;
    }
}
