<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\CommitCheck;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CommitCheckTest extends TestCase
{
    private const string REGEX = '/^([A-Z][A-Z0-9]*)-\d+/';

    #[Test]
    public function passesWhenKeysMatch(): void
    {
        $check = new CommitCheck('PROJ-42-fix-login', 'PROJ-42 fix login timeout', self::REGEX);

        $check->validate();

        self::assertTrue(true, 'matching keys must not throw');
    }

    #[Test]
    public function passesWhenNeitherHasKey(): void
    {
        $check = new CommitCheck('main', 'routine cleanup', self::REGEX);

        $check->validate();

        self::assertTrue(true, 'no keys on either side must skip validation');
    }

    #[Test]
    public function passesForMergeCommit(): void
    {
        $check = new CommitCheck('PROJ-42-feature', 'Merge branch \'main\' into PROJ-42-feature', self::REGEX);

        $check->validate();

        self::assertTrue(true, 'merge commits must be skipped');
    }

    #[Test]
    public function passesForPullRequestMerge(): void
    {
        $check = new CommitCheck('PROJ-99-hotfix', 'Merge pull request #5 from org/PROJ-99-hotfix', self::REGEX);

        $check->validate();

        self::assertTrue(true, 'pull request merges must be skipped');
    }

    #[Test]
    public function throwsWhenMessageHasKeyButBranchDoesNot(): void
    {
        $check = new CommitCheck('feature-login', 'CORE-77 add login page', self::REGEX);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('branch has no issue key');

        $check->validate();
    }

    #[Test]
    public function throwsWhenMessageHasNoKey(): void
    {
        $check = new CommitCheck('ACME-100-refactor', 'improve performance', self::REGEX);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('commit message has no issue key');

        $check->validate();
    }

    #[Test]
    public function throwsWhenKeysDiffer(): void
    {
        $check = new CommitCheck('ACME-100-refactor', 'ACME-200 wrong task reference', self::REGEX);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('differs from commit message');

        $check->validate();
    }

    #[Test]
    public function throwsWhenProjectsDiffer(): void
    {
        $check = new CommitCheck('SHOP-55-cart', 'WEB-55 update cart logic', self::REGEX);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('differs from commit message');

        $check->validate();
    }

    #[Test]
    public function throwsOnInvalidRegex(): void
    {
        $check = new CommitCheck('PROJ-1-work', 'PROJ-1 done', '/(unclosed');

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Invalid project regex');

        $check->validate();
    }
}
