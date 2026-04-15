<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\ProjectVersions;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectVersionsTest extends TestCase
{
    #[Test]
    public function returnsMatchingVersionsSortedBySemver(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/project/PLAT/version?status=unreleased&orderBy=name&startAt=0' => [
                'values' => [
                    ['name' => 'PLAT 2.1.0'],
                    ['name' => 'PLAT 1.3.0'],
                    ['name' => 'PLAT 2.0.1'],
                ],
            ],
        ]);

        self::assertSame(
            ['PLAT 1.3.0', 'PLAT 2.0.1', 'PLAT 2.1.0'],
            (new ProjectVersions($http, 'PLAT'))->names(),
            'versions must be sorted by semver',
        );
    }

    #[Test]
    public function filtersOutNonMatchingVersions(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/project/OPS/version?status=unreleased&orderBy=name&startAt=0' => [
                'values' => [
                    ['name' => 'OPS 3.0.0'],
                    ['name' => 'Sprint 42'],
                    ['name' => 'OPS-hotfix'],
                    ['name' => 'OPS 3.1.0'],
                ],
            ],
        ]);

        self::assertSame(
            ['OPS 3.0.0', 'OPS 3.1.0'],
            (new ProjectVersions($http, 'OPS'))->names(),
            'non-semver versions must be excluded',
        );
    }

    #[Test]
    public function returnsEmptyListWhenNoVersionsMatch(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/project/CORE/version?status=unreleased&orderBy=name&startAt=0' => [
                'values' => [
                    ['name' => 'Backlog'],
                ],
            ],
        ]);

        self::assertSame(
            [],
            (new ProjectVersions($http, 'CORE'))->names(),
            'empty list when no versions match pattern',
        );
    }

    #[Test]
    public function collectsVersionsAcrossPages(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/project/NAV/version?status=unreleased&orderBy=name&startAt=0' => [
                'values' => [
                    ['name' => 'NAV 1.0.0'],
                ],
                'maxResults' => 1,
                'isLast' => false,
            ],
            'GET /rest/api/3/project/NAV/version?status=unreleased&orderBy=name&startAt=1' => [
                'values' => [
                    ['name' => 'NAV 2.0.0'],
                ],
                'maxResults' => 1,
                'isLast' => true,
            ],
        ]);

        self::assertSame(
            ['NAV 1.0.0', 'NAV 2.0.0'],
            (new ProjectVersions($http, 'NAV'))->names(),
            'versions from multiple pages must be merged',
        );
    }

    #[Test]
    public function skipsNonArrayEntries(): void
    {
        $http = new FakeHttp([
            'GET /rest/api/3/project/BEAM/version?status=unreleased&orderBy=name&startAt=0' => [
                'values' => [
                    'not-an-array',
                    ['name' => 'BEAM 1.0.0'],
                ],
            ],
        ]);

        self::assertSame(
            ['BEAM 1.0.0'],
            (new ProjectVersions($http, 'BEAM'))->names(),
            'non-array entries must be skipped',
        );
    }
}
