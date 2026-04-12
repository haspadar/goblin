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
        $reflog = $this->exec('git reflog --date=iso');
        $pattern = '/checkout: moving from ([^ ]+) to '
            . preg_quote($branch, '/') . '$/m';
        $count = preg_match_all($pattern, $reflog, $m);

        if ($count === 0 || $count === false) {
            throw new GoblinException('Failed to determine parent branch');
        }

        return $m[1][$count - 1];
    }

    #[Override]
    public function remote(): string
    {
        $remote = trim($this->exec('git remote get-url origin'));

        if ($remote === '') {
            throw new GoblinException('Failed to determine remote URL');
        }

        return $remote;
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
