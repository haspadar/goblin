<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\ShellGit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShellGitTest extends TestCase
{
    #[Test]
    public function returnsCurrentBranch(): void
    {
        $git = new ShellGit();

        self::assertNotSame(
            '',
            $git->currentBranch(),
            'current branch must not be empty in a git repo',
        );
    }

    #[Test]
    public function returnsParentBranch(): void
    {
        $git = new ShellGit();

        self::assertNotSame(
            '',
            $git->parentBranch(),
            'parent branch must be resolvable from reflog',
        );
    }
}
