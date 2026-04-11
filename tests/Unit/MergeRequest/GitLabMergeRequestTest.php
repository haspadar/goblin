<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\MergeRequest;

use Goblin\MergeRequest\GitLabMergeRequest;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GitLabMergeRequestTest extends TestCase
{
    #[Test]
    public function createsWithCorrectPayload(): void
    {
        $http = new FakeHttp([
            'POST /projects/web%2Fportal/merge_requests' => [
                'iid' => 42,
                'title' => 'Enable dark mode',
            ],
        ]);

        $mr = new GitLabMergeRequest($http, 'web/portal');

        $mr->create([
            'source_branch' => 'dark-mode',
            'target_branch' => 'main',
            'title' => 'Enable dark mode',
            'description' => 'Adds theme toggle',
        ]);

        self::assertSame(
            [
                'source_branch' => 'dark-mode',
                'target_branch' => 'main',
                'title' => 'Enable dark mode',
                'description' => 'Adds theme toggle',
            ],
            $http->lastBody(),
            'create must send all params as request body',
        );
    }

    #[Test]
    public function viewsExistingMergeRequest(): void
    {
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'GET /projects/data%2Fpipeline/merge_requests/8' => [
                    'iid' => 8,
                    'state' => 'merged',
                    'title' => 'Partition audit logs',
                ],
            ]),
            'data/pipeline',
        );

        self::assertSame(
            'merged',
            $mr->view(8)['state'],
            'viewed MR must include state from API',
        );
    }

    #[Test]
    public function listsWithFilters(): void
    {
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'GET /projects/mobile%2Fapp/merge_requests?state=opened' => [
                    ['iid' => 3],
                    ['iid' => 5],
                ],
            ]),
            'mobile/app',
        );

        self::assertCount(
            2,
            $mr->list(['state' => 'opened']),
            'list must return filtered MRs from API',
        );
    }

    #[Test]
    public function updatesFields(): void
    {
        $http = new FakeHttp([
            'PUT /projects/infra%2Fdeploy/merge_requests/11' => [
                'iid' => 11,
                'title' => 'Canary rollout v2',
            ],
        ]);

        $mr = new GitLabMergeRequest($http, 'infra/deploy');

        $mr->update(11, ['title' => 'Canary rollout v2']);

        self::assertSame(
            ['title' => 'Canary rollout v2'],
            $http->lastBody(),
            'update must send changes as request body',
        );
    }

    #[Test]
    public function listsWithoutFilters(): void
    {
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'GET /projects/ops%2Fmonitor/merge_requests' => [
                    ['iid' => 1],
                ],
            ]),
            'ops/monitor',
        );

        self::assertCount(
            1,
            $mr->list(),
            'list without filters must request all MRs',
        );
    }
}
