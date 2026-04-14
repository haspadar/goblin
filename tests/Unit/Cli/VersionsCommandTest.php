<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\VersionsCommand;
use Goblin\GoblinException;
use Goblin\Issue\ProjectKey;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeHttp;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VersionsCommandTest extends TestCase
{
    #[Test]
    public function printsVersionsWithBranches(): void
    {
        $output = new FakeOutput();
        $cmd = new VersionsCommand(
            new FakeHttp([
                'GET /rest/api/3/project/PLAT/version?status=unreleased&orderBy=name' => [
                    ['name' => 'PLAT 3.0.0'],
                    ['name' => 'PLAT 3.1.0'],
                ],
            ]),
            new ProjectKey('PLAT', new FakeGit('main'), $this->config()),
            $output,
        );

        $code = $cmd->run(new Arguments([], []));

        self::assertSame(0, $code, 'versions command must return zero');
        self::assertStringContainsString('PLAT 3.0.0', $output->infos[0], 'output must list first version');
    }

    #[Test]
    public function showsExtraInfoWhenVerbose(): void
    {
        $output = new FakeOutput();
        $cmd = new VersionsCommand(
            new FakeHttp([
                'GET /rest/api/3/project/BEAM/version?status=unreleased&orderBy=name' => [
                    ['name' => 'BEAM 2.0.0'],
                ],
            ]),
            new ProjectKey('BEAM', new FakeGit('main'), $this->config()),
            $output,
        );

        $cmd->run(new Arguments(['verbose' => true], []));

        self::assertStringContainsString('BEAM', $output->muted[0], 'verbose must show project name');
        self::assertCount(1, $output->successes, 'verbose must show done message');
    }

    #[Test]
    public function skipsExtraInfoWithoutVerbose(): void
    {
        $output = new FakeOutput();
        $cmd = new VersionsCommand(
            new FakeHttp([
                'GET /rest/api/3/project/OPS/version?status=unreleased&orderBy=name' => [
                    ['name' => 'OPS 1.0.0'],
                ],
            ]),
            new ProjectKey('OPS', new FakeGit('main'), $this->config()),
            $output,
        );

        $cmd->run(new Arguments([], []));

        self::assertCount(0, $output->muted, 'non-verbose must skip muted output');
        self::assertCount(0, $output->successes, 'non-verbose must skip done message');
    }

    #[Test]
    public function throwsWhenNoVersionsFound(): void
    {
        $cmd = new VersionsCommand(
            new FakeHttp([
                'GET /rest/api/3/project/CORE/version?status=unreleased&orderBy=name' => [
                    ['name' => 'Sprint 99'],
                ],
            ]),
            new ProjectKey('CORE', new FakeGit('main'), $this->config()),
            new FakeOutput(),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('No unreleased versions');

        $cmd->run(new Arguments([], []));
    }

    private function config(): FakeConfig
    {
        return new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']);
    }
}
