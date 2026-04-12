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
    public function returnsNonEmptyBranchName(): void
    {
        $branch = (new ShellGit())->currentBranch();

        self::assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._\/-]+$/',
            $branch,
            'branch name must contain only valid git characters',
        );
    }
}
