<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;
use Override;

/**
 * Git state from local shell commands.
 */
final readonly class ShellGit implements Git
{
    #[Override]
    public function currentBranch(): string
    {
        $branch = trim($this->exec('git rev-parse --abbrev-ref HEAD'));

        if ($branch === '') {
            throw new GoblinException('Failed to determine current branch');
        }

        return $branch;
    }

    #[Override]
    public function parentBranch(): string
    {
        $branch = $this->currentBranch();
        $cmd = sprintf(
            "git reflog --date=iso | grep 'checkout: moving from' | grep 'to %s' | tail -n 1",
            escapeshellarg($branch),
        );
        $output = trim($this->exec($cmd));

        if (preg_match('/moving from ([^ ]+) to/', $output, $m) !== 1) {
            throw new GoblinException('Failed to determine parent branch');
        }

        return $m[1];
    }

    /**
     * Executes a shell command and returns its output.
     *
     * @throws GoblinException
     */
    private function exec(string $cmd): string
    {
        exec($cmd, $lines, $code);

        if ($code !== 0) {
            throw new GoblinException("Command failed ({$code}): {$cmd}");
        }

        return implode("\n", $lines);
    }
}
