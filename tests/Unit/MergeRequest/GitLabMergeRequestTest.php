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
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'POST /projects/web%2Fportal/merge_requests' => [
                    'iid' => 42,
                    'title' => 'Enable dark mode',
                ],
            ]),
            'web/portal',
        );

        $result = $mr->create([
            'source_branch' => 'dark-mode',
            'target_branch' => 'main',
            'title' => 'Enable dark mode',
            'description' => 'Adds theme toggle',
        ]);

        self::assertSame(
            42,
            $result['iid'],
            'created MR must return iid from API response',
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
                    'items' => [['iid' => 3], ['iid' => 5]],
                ],
            ]),
            'mobile/app',
        );

        self::assertCount(
            2,
            $mr->list(['state' => 'opened'])['items'],
            'list must return filtered MRs from API',
        );
    }

    #[Test]
    public function updatesFields(): void
    {
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'PUT /projects/infra%2Fdeploy/merge_requests/11' => [
                    'iid' => 11,
                    'title' => 'Canary rollout v2',
                ],
            ]),
            'infra/deploy',
        );

        self::assertSame(
            'Canary rollout v2',
            $mr->update(11, ['title' => 'Canary rollout v2'])['title'],
            'update must pass changes to API',
        );
    }

    #[Test]
    public function listsWithoutFilters(): void
    {
        $mr = new GitLabMergeRequest(
            new FakeHttp([
                'GET /projects/ops%2Fmonitor/merge_requests' => [
                    'items' => [['iid' => 1]],
                ],
            ]),
            'ops/monitor',
        );

        self::assertCount(
            1,
            $mr->list()['items'],
            'list without filters must request all MRs',
        );
    }
}
