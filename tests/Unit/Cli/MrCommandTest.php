<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\MrCommand;
use Goblin\GoblinException;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MrCommandTest extends TestCase
{
    #[Test]
    public function createsWithDraftPrefix(): void
    {
        $http = new FakeHttp([
            'POST /projects/team%2Fapp/merge_requests' => [
                'iid' => 15,
                'title' => 'Draft: Add caching layer',
            ],
        ]);

        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:team/app.git'),
            $http,
        );

        ob_start();
        $cmd->run(new Arguments('mr', [
            'source' => 'add-caching',
            'target' => 'main',
            'title' => 'Add caching layer',
            'description' => 'Redis-based cache',
            'draft' => true,
        ], ['create']));
        ob_end_clean();

        self::assertSame(
            'Draft: Add caching layer',
            $http->lastBody()['title'],
            'create with --draft must prepend Draft: prefix',
        );
    }

    #[Test]
    public function viewsExistingMergeRequest(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:ops/deploy.git'),
            new FakeHttp([
                'GET /projects/ops%2Fdeploy/merge_requests/7' => [
                    'iid' => 7,
                    'state' => 'opened',
                    'title' => 'Canary rollout',
                ],
            ]),
        );

        ob_start();
        $cmd->run(new Arguments('mr', [], ['view', '7']));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'Canary rollout',
            $output,
            'view must output merge request title',
        );
    }

    #[Test]
    public function listsWithStateFilter(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:web/site.git'),
            new FakeHttp([
                'GET /projects/web%2Fsite/merge_requests?state=merged' => [
                    ['iid' => 3],
                    ['iid' => 9],
                ],
            ]),
        );

        ob_start();
        $cmd->run(new Arguments('mr', ['state' => 'merged'], ['list']));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            '"iid": 9',
            $output,
            'list must output filtered merge requests as JSON',
        );
    }

    #[Test]
    public function updatesWithReadyFlag(): void
    {
        $http = new FakeHttp([
            'GET /projects/infra%2Fci/merge_requests/4' => [
                'iid' => 4,
                'title' => 'Draft: Pipeline speedup',
            ],
            'PUT /projects/infra%2Fci/merge_requests/4' => [
                'iid' => 4,
                'title' => 'Pipeline speedup',
            ],
        ]);

        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:infra/ci.git'),
            $http,
        );

        ob_start();
        $cmd->run(new Arguments('mr', ['ready' => true], ['update', '4']));
        ob_end_clean();

        self::assertSame(
            'Pipeline speedup',
            $http->lastBody()['title'],
            'update with --ready must remove Draft: prefix',
        );
    }

    #[Test]
    public function throwsForUnknownSubcommand(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:any/project.git'),
            new FakeHttp([]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Unknown mr subcommand');

        $cmd->run(new Arguments('mr', [], ['merge']));
    }

    #[Test]
    public function throwsForMissingIid(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:any/project.git'),
            new FakeHttp([]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('IID is required');

        $cmd->run(new Arguments('mr', [], ['view']));
    }

    #[Test]
    public function throwsForMissingRequiredCreateOptions(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:any/project.git'),
            new FakeHttp([]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('--source, --target and --title are required');

        $cmd->run(new Arguments('mr', [], ['create']));
    }

    #[Test]
    public function throwsForConflictingDraftAndReady(): void
    {
        $cmd = new MrCommand(
            new FakeGit('feature', 'main', 'git@gitlab.example.com:any/project.git'),
            new FakeHttp([]),
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('--draft and --ready are mutually exclusive');

        $cmd->run(new Arguments('mr', ['draft' => true, 'ready' => true], ['update', '5']));
    }
}
