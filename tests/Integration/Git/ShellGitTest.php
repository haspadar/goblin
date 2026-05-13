<?php

declare(strict_types=1);

namespace Goblin\Tests\Integration\Git;

use Goblin\Git\ShellGit;
use Goblin\GoblinException;
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

    #[Test]
    public function recognisesHeadAsItsOwnAncestor(): void
    {
        self::assertTrue(
            (new ShellGit())->isAncestor('HEAD'),
            'HEAD must be reported as ancestor of itself',
        );
    }

    #[Test]
    public function throwsWhenAskedAboutUnknownRef(): void
    {
        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Command failed');

        (new ShellGit())->isAncestor('no-such-ref-goblin-isancestor-probe');
    }
}
