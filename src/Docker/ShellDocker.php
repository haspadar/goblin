<?php

declare(strict_types=1);

namespace Goblin\Docker;

use Goblin\Output\Output;
use Override;

/**
 * Docker operations via local shell commands.
 */
final readonly class ShellDocker implements Docker
{
    /**
     * Stores output channel.
     */
    public function __construct(private Output $output) {}

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
        exec(
            'docker exec ' . escapeshellarg($container) . ' ' . $command . ' 2>&1',
            $lines,
            $code,
        );
        $this->output->info(implode("\n", $lines));

        return $code;
    }
}
