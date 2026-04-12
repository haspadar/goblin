<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\MergeRequest;

use Goblin\GoblinException;
use Goblin\MergeRequest\ProjectPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectPathTest extends TestCase
{
    #[Test]
    public function extractsFromSshRemote(): void
    {
        self::assertSame(
            'acme/billing',
            (new ProjectPath('git@gitlab.acme.io:acme/billing.git'))->value(),
            'SSH remote must resolve to group/project',
        );
    }

    #[Test]
    public function extractsFromHttpsRemote(): void
    {
        self::assertSame(
            'ops/infra-tools',
            (new ProjectPath('https://gitlab.example.com/ops/infra-tools.git'))->value(),
            'HTTPS remote must resolve to group/project',
        );
    }

    #[Test]
    public function extractsFromHttpsWithoutDotGit(): void
    {
        self::assertSame(
            'team/service',
            (new ProjectPath('https://gitlab.internal/team/service'))->value(),
            'HTTPS remote without .git suffix must still resolve',
        );
    }

    #[Test]
    public function extractsNestedGroups(): void
    {
        self::assertSame(
            'org/sub/deep/repo',
            (new ProjectPath('git@git.corp.net:org/sub/deep/repo.git'))->value(),
            'nested group paths must be preserved',
        );
    }

    #[Test]
    public function throwsForUnrecognizedFormat(): void
    {
        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Cannot extract project path');

        (new ProjectPath('/local/path/not/a/remote'))->value();
    }
}
