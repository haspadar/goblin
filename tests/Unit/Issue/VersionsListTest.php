<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\GoblinException;
use Goblin\Issue\VersionsList;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VersionsListTest extends TestCase
{
    #[Test]
    public function returnsVersionNameInPair(): void
    {
        $list = new VersionsList(
            new FakeHttp([
                'GET /rest/api/3/project/PLAT/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'PLAT 3.0.0']],
                ],
            ]),
            'PLAT',
            [],
        );

        self::assertSame('PLAT 3.0.0', $list->pairs()[0]['version'], 'pair must contain version name');
    }

    #[Test]
    public function returnsBranchInPair(): void
    {
        $list = new VersionsList(
            new FakeHttp([
                'GET /rest/api/3/project/PLAT/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'PLAT 3.0.0']],
                ],
            ]),
            'PLAT',
            [],
        );

        self::assertSame('dev', $list->pairs()[0]['branch'], 'single version without rules must map to dev');
    }

    #[Test]
    public function throwsWhenNoMatchingVersions(): void
    {
        $list = new VersionsList(
            new FakeHttp([
                'GET /rest/api/3/project/CORE/version?status=unreleased&orderBy=name&startAt=0' => [
                    'values' => [['name' => 'Sprint 99']],
                ],
            ]),
            'CORE',
            [],
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('No unreleased versions');

        $list->pairs();
    }
}
