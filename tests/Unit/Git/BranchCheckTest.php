<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\BranchCheck;
use Goblin\GoblinException;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BranchCheckTest extends TestCase
{
    #[Test]
    public function skipsProtectedBranch(): void
    {
        $check = new BranchCheck(
            new FakeGit('dev', 'main'),
            new FakeHttp([]),
            new FakeConfig([
                'protected-branches' => ['main', 'dev', 'stage', 'beta', 'master'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'protected branches must skip validation');
    }

    #[Test]
    public function skipsNonProjectBranch(): void
    {
        $check = new BranchCheck(
            new FakeGit('feature-refactor', 'main'),
            new FakeHttp([]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'branches without issue key must skip validation');
    }

    #[Test]
    public function passesWhenParentMatchesBetaRule(): void
    {
        $check = new BranchCheck(
            new FakeGit('SHOP-42-login-page', 'beta'),
            new FakeHttp([
                'GET /rest/api/3/issue/SHOP-42' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'SHOP 14.0.1']],
                    ],
                ],
                'GET /rest/api/3/project/SHOP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'SHOP 14.0.0', 'released' => false],
                        ['name' => 'SHOP 14.0.1', 'released' => false],
                        ['name' => 'SHOP 15.0.0', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                    'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                    'default' => 'dev',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'correct parent branch must pass validation');
    }

    #[Test]
    public function throwsWhenParentDiffers(): void
    {
        $check = new BranchCheck(
            new FakeGit('PAY-99-payment', 'stage'),
            new FakeHttp([
                'GET /rest/api/3/issue/PAY-99' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'PAY 10.0.1']],
                    ],
                ],
                'GET /rest/api/3/project/PAY/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'PAY 10.0.0', 'released' => false],
                        ['name' => 'PAY 10.0.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                    'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                    'default' => 'dev',
                ],
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'beta', but branch was created from 'stage'");

        $check->validate();
    }

    #[Test]
    public function throwsWhenNoFixVersion(): void
    {
        $check = new BranchCheck(
            new FakeGit('INFRA-7-deploy', 'main'),
            new FakeHttp([
                'GET /rest/api/3/issue/INFRA-7' => [
                    'fields' => ['fixVersions' => []],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('has no Fix Version');

        $check->validate();
    }

    #[Test]
    public function throwsOnInvalidProjectRegex(): void
    {
        $check = new BranchCheck(
            new FakeGit('CORE-1-task', 'main'),
            new FakeHttp([]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/(unclosed',
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Invalid project regex');

        $check->validate();
    }

    #[Test]
    public function throwsWhenFixVersionNameIsNotString(): void
    {
        $check = new BranchCheck(
            new FakeGit('HUB-5-api', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/HUB-5' => [
                    'fields' => [
                        'fixVersions' => [['id' => '999']],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('has no Fix Version');

        $check->validate();
    }

    #[Test]
    public function patchHotfixForkedFromMasterPassesWhenBetaAlsoAllowed(): void
    {
        $check = new BranchCheck(
            new FakeGit('CRS-77-bank-timeout', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/CRS-77' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'CRS 2.3.1']],
                    ],
                ],
                'GET /rest/api/3/project/CRS/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'CRS 2.3.0', 'released' => false],
                        ['name' => 'CRS 2.3.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main', 'master'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['beta', 'master'],
                    ],
                    'default' => 'develop',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'patch forked off master must pass when master is in bases');
    }

    #[Test]
    public function continuationBranchOffBetaPassesForPatchRelease(): void
    {
        $check = new BranchCheck(
            new FakeGit('PAY-204-retry-policy', 'beta'),
            new FakeHttp([
                'GET /rest/api/3/issue/PAY-204' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'PAY 11.0.1']],
                    ],
                ],
                'GET /rest/api/3/project/PAY/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'PAY 11.0.1', 'released' => false]],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['beta'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['beta', 'master'],
                    ],
                    'default' => 'trunk',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'follow-up patch must pass when forked off the same beta line');
    }

    #[Test]
    public function errorEnumeratesEveryBaseWhenParentIsForeign(): void
    {
        $check = new BranchCheck(
            new FakeGit('SHOP-9012-promo-coupon', 'staging'),
            new FakeHttp([
                'GET /rest/api/3/issue/SHOP-9012' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'SHOP 18.4.1']],
                    ],
                ],
                'GET /rest/api/3/project/SHOP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'SHOP 18.4.1', 'released' => false]],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['staging', 'release'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'release' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['release', 'hotfix'],
                    ],
                    'default' => 'main',
                ],
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'release' or 'hotfix', but branch was created from 'staging'");

        $check->validate();
    }

    #[Test]
    public function singleBaseStringRedirectsForkPointAwayFromTarget(): void
    {
        $check = new BranchCheck(
            new FakeGit('AUTH-318-saml-rotation', 'release/4.x'),
            new FakeHttp([
                'GET /rest/api/3/issue/AUTH-318' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'AUTH 6.5.1']],
                    ],
                ],
                'GET /rest/api/3/project/AUTH/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'AUTH 6.5.1', 'released' => false]],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['release/4.x'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'qa' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => 'release/4.x',
                    ],
                    'default' => 'next',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'string base must point fork at a branch other than the rule key');
    }

    #[Test]
    public function rejectsTargetWhenItIsNotPartOfDeclaredBases(): void
    {
        $rules = [
            'release-train' => [
                'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                'base' => ['hardening', 'preview'],
            ],
            'default' => 'core',
        ];

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'hardening' or 'preview', but branch was created from 'release-train'");

        (new BranchCheck(
            new FakeGit('GROW-2105-funnel-tweak', 'release-train'),
            new FakeHttp([
                'GET /rest/api/3/issue/GROW-2105' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'GROW 25.10.1']],
                    ],
                ],
                'GET /rest/api/3/project/GROW/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'GROW 25.10.1', 'released' => false]],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['hardening', 'preview', 'release-train'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => $rules,
            ]),
        ))->validate();
    }

    #[Test]
    public function skipsNonArrayVersionEntries(): void
    {
        $check = new BranchCheck(
            new FakeGit('DATA-3-smoke', 'dev'),
            new FakeHttp([
                'GET /rest/api/3/issue/DATA-3' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'DATA 8.0.0']],
                    ],
                ],
                'GET /rest/api/3/project/DATA/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        'invalid-string',
                        ['name' => 'DATA 8.0.0', 'released' => false],
                        ['name' => 'DATA 8.0.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                    'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                    'default' => 'dev',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'non-array entries in versions list must be skipped');
    }
}
