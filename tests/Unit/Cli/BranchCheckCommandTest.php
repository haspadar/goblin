<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\BranchCheckCommand;
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
            $cmd->run(new Arguments('branch-check', [], [])),
            'protected branch must pass validation',
        );
    }

    #[Test]
    public function throwsForWrongBaseBranch(): void
    {
        $cmd = new BranchCheckCommand(
            new FakeGit('PROJ-123-feature', 'dev'),
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-123' => [
                    'fields' => [
                        'fixVersions' => [
                            ['name' => 'PROJ 2.0.0'],
                        ],
                    ],
                ],
                'GET /rest/api/3/project/PROJ/version?status=unreleased&orderBy=name' => [
                    ['name' => 'PROJ 2.0.0'],
                ],
            ]),
            new FakeConfig([
                'protected-branches' => ['main'],
                'project-regex' => '/^([A-Z]+)-\d+/',
            ]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('requires base');

        $cmd->run(new Arguments('branch-check', [], []));
    }
}
