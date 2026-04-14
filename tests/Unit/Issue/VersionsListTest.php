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
    public function returnsPairsWithTargetBranches(): void
    {
        $list = new VersionsList(
            new FakeHttp([
                'GET /rest/api/3/project/PLAT/version?status=unreleased&orderBy=name' => [
                    ['name' => 'PLAT 3.0.0'],
                    ['name' => 'PLAT 3.1.0'],
                ],
            ]),
            'PLAT',
        );

        $pairs = $list->pairs();

        self::assertSame('PLAT 3.0.0', $pairs[0]['version'], 'first pair must contain version name');
        self::assertArrayHasKey('branch', $pairs[0], 'each pair must contain branch');
    }

    #[Test]
    public function throwsWhenNoVersionsFound(): void
    {
        $list = new VersionsList(
            new FakeHttp([
                'GET /rest/api/3/project/CORE/version?status=unreleased&orderBy=name' => [
                    ['name' => 'Sprint 99'],
                ],
            ]),
            'CORE',
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('No unreleased versions');

        $list->pairs();
    }
}
