<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\BranchRules;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VersionMappingTest extends TestCase
{
    #[Test]
    public function mapsToDefaultWhenNoRulesMatch(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.0', 'PROJ 15.0.0'],
            ['default' => 'dev'],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('PROJ 14.0.0'),
            'unmatched versions must map to default branch',
        );
    }

    #[Test]
    public function mapsToDefaultWhenNoBetaMatches(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.0', 'PROJ 15.0.0'],
            $this->rules(),
        );

        self::assertSame(
            'dev',
            $rules->branchFor('PROJ 14.0.0'),
            'without beta matches versions must fall through to default',
        );
    }

    #[Test]
    public function mapsBetaToBeta(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 15.0.0'],
            $this->rules(),
        );

        self::assertSame(
            'beta',
            $rules->branchFor('PROJ 14.0.1'),
            'X.Y.1 version must map to beta',
        );
    }

    #[Test]
    public function mapsRemainingToDefault(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 15.0.0'],
            $this->rules(),
        );

        self::assertSame(
            'dev',
            $rules->branchFor('PROJ 15.0.0'),
            'version not matching any rule must map to default',
        );
    }

    #[Test]
    public function mapsStageByTemplate(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 14.1.0', 'PROJ 15.0.0'],
            $this->rules(),
        );

        self::assertSame(
            'stage',
            $rules->branchFor('PROJ 14.1.0'),
            'version matching stage template must map to stage',
        );
    }

    #[Test]
    public function picksMaxBetaByDefault(): void
    {
        $rules = new BranchRules(
            ['PROJ 14.0.1', 'PROJ 15.0.1', 'PROJ 16.0.0'],
            $this->rules(),
        );

        self::assertSame(
            'beta',
            $rules->branchFor('PROJ 15.0.1'),
            'sort desc must pick maximum beta version',
        );
    }

    #[Test]
    public function throwsForUnknownVersion(): void
    {
        $rules = new BranchRules(['PROJ 14.0.0'], $this->rules());

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('not found among active releases');

        $rules->branchFor('PROJ 99.0.0');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
            'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
            'default' => 'dev',
        ];
    }
}
