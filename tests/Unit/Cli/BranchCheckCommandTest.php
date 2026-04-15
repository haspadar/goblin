<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\BranchCheckCommand;
use Goblin\GoblinException;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BranchCheckCommandTest extends TestCase
{
    #[Test]
    public function returnsZeroForProtectedBranch(): void
    {
        $cmd = new BranchCheckCommand(
            new FakeGit('main'),
            new FakeHttp([]),
            new FakeConfig([
                'protected-branches' => ['main', 'dev'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        self::assertSame(
            0,
            $cmd->run(new Arguments([], [])),
            'protected branch must pass validation',
        );
    }

    #[Test]
    public function throwsForWrongBaseBranch(): void
    {
        $cmd = new BranchCheckCommand(
            new FakeGit('PROJ-123-feature', 'stage'),
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-123' => [
                    'fields' => [
                        'fixVersions' => [
                            ['name' => 'PROJ 2.0.1'],
                        ],
                    ],
                ],
                'GET /rest/api/3/project/PROJ/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [
                        ['name' => 'PROJ 2.0.0'],
                        ['name' => 'PROJ 2.0.1'],
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
        $this->expectExceptionMessage('requires base');

        $cmd->run(new Arguments([], []));
    }
}
