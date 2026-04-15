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
    public function passesWhenParentMatchesExpected(): void
    {
        $check = new BranchCheck(
            new FakeGit('MSP-42-login-page', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/MSP-42' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'MSP 14.0.0']],
                    ],
                ],
                'GET /rest/api/3/project/MSP/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'MSP 14.0.0', 'released' => false],
                        ['name' => 'MSP 14.0.1', 'released' => false],
                        ['name' => 'MSP 15.0.0', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'correct parent branch must pass validation');
    }

    #[Test]
    public function throwsWhenParentDiffers(): void
    {
        $check = new BranchCheck(
            new FakeGit('CRS-99-payment', 'dev'),
            new FakeHttp([
                'GET /rest/api/3/issue/CRS-99' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'CRS 10.0.0']],
                    ],
                ],
                'GET /rest/api/3/project/CRS/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'CRS 10.0.0', 'released' => false],
                        ['name' => 'CRS 10.0.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage("requires base 'master', but branch was created from 'dev'");

        $check->validate();
    }

    #[Test]
    public function throwsWhenNoFixVersion(): void
    {
        $check = new BranchCheck(
            new FakeGit('OPS-7-deploy', 'main'),
            new FakeHttp([
                'GET /rest/api/3/issue/OPS-7' => [
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
            new FakeGit('PROJ-1-task', 'main'),
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
            new FakeGit('DEV-5-api', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/DEV-5' => [
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
    public function skipsNonArrayVersionEntries(): void
    {
        $check = new BranchCheck(
            new FakeGit('QA-3-smoke', 'master'),
            new FakeHttp([
                'GET /rest/api/3/issue/QA-3' => [
                    'fields' => [
                        'fixVersions' => [['name' => 'QA 8.0.0']],
                    ],
                ],
                'GET /rest/api/3/project/QA/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        'invalid-string',
                        ['name' => 'QA 8.0.0', 'released' => false],
                        ['name' => 'QA 8.0.1', 'released' => false],
                    ],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $check->validate();

        self::assertTrue(true, 'non-array entries in versions list must be skipped');
    }
}
