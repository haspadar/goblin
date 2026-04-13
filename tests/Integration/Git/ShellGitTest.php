<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Git;

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
    public function returnsCleanBranchName(): void
    {
        $branch = (new ShellGit())->currentBranch();

        self::assertDoesNotMatchRegularExpression(
            '/[\r\n]/',
            $branch,
            'branch name must not contain CR or LF',
        );
    }
}
