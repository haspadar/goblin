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
    public function passesWhenParentIsMasterWithMultipleBases(): void
    {
        $check = new BranchCheck(
            new FakeGit('MSP-100-payment-retry', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/MSP-100' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'MSP 1.14.1']],
                    ],
                ],
                'GET /rest/api/3/project/MSP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'MSP 1.14.0', 'released' => false],
                        ['name' => 'MSP 1.14.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['beta', 'master'],
                    ],
                    'default' => 'dev',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'master as one of multiple bases must pass validation');
    }

    #[Test]
    public function passesWhenParentIsBetaWithMultipleBases(): void
    {
        $check = new BranchCheck(
            new FakeGit('MSP-101-refund', 'beta'),
            new FakeHttp([
                'GET /rest/api/3/issue/MSP-101' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'MSP 1.14.1']],
                    ],
                ],
                'GET /rest/api/3/project/MSP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'MSP 1.14.0', 'released' => false],
                        ['name' => 'MSP 1.14.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['beta', 'master'],
                    ],
                    'default' => 'dev',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'beta as one of multiple bases must pass validation');
    }

    #[Test]
    public function listsAllAllowedBasesInErrorMessage(): void
    {
        $check = new BranchCheck(
            new FakeGit('MSP-102-settlement', 'stage'),
            new FakeHttp([
                'GET /rest/api/3/issue/MSP-102' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'MSP 2.7.1']],
                    ],
                ],
                'GET /rest/api/3/project/MSP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'MSP 2.7.0', 'released' => false],
                        ['name' => 'MSP 2.7.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => ['beta', 'master'],
                    ],
                    'default' => 'dev',
                ],
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'beta' or 'master', but branch was created from 'stage'");

        $check->validate();
    }

    #[Test]
    public function acceptsSingleBaseOverridingTarget(): void
    {
        $check = new BranchCheck(
            new FakeGit('BRS-55-chargeback', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/BRS-55' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'BRS 3.0.1']],
                    ],
                ],
                'GET /rest/api/3/project/BRS/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'BRS 3.0.0', 'released' => false],
                        ['name' => 'BRS 3.0.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => 'master',
                    ],
                    'default' => 'dev',
                ],
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'string base must override target and accept that branch');
    }

    #[Test]
    public function rejectsTargetWhenSingleBaseOverrides(): void
    {
        $check = new BranchCheck(
            new FakeGit('BRS-56-dispute', 'beta'),
            new FakeHttp([
                'GET /rest/api/3/issue/BRS-56' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'BRS 4.1.1']],
                    ],
                ],
                'GET /rest/api/3/project/BRS/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'BRS 4.1.0', 'released' => false],
                        ['name' => 'BRS 4.1.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
                'branch-rules' => [
                    'beta' => [
                        'match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/',
                        'base' => 'master',
                    ],
                    'default' => 'dev',
                ],
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'master', but branch was created from 'beta'");

        $check->validate();
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
