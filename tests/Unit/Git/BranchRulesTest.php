<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\BranchRules;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BranchRulesTest extends TestCase
{
    #[Test]
    public function fallsBackToDevWithEmptyRules(): void
    {
        $rules = new BranchRules(['ACME 3.2.0'], []);

        self::assertSame(
            'dev',
            $rules->branchFor('ACME 3.2.0'),
            'empty rules must fall back to dev',
        );
    }

    #[Test]
    public function mapsToDefaultWhenNoRuleMatches(): void
    {
        $rules = new BranchRules(
            ['SHOP 7.0.0', 'SHOP 8.0.0'],
            ['default' => 'develop'],
        );

        self::assertSame(
            'develop',
            $rules->branchFor('SHOP 8.0.0'),
            'unmatched versions must map to configured default branch',
        );
    }

    #[Test]
    public function mapsSingleReleaseToDefault(): void
    {
        $rules = new BranchRules(
            ['WIKI 1.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'default' => 'trunk',
            ],
        );

        self::assertSame(
            'trunk',
            $rules->branchFor('WIKI 1.0.0'),
            'single release not matching any rule must map to default',
        );
    }

    #[Test]
    public function matchesPatchOneAsBeta(): void
    {
        $rules = new BranchRules(
            ['PAY 5.0.0', 'PAY 5.0.1'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('PAY 5.0.1'),
            'patch-1 version must map to beta',
        );
    }

    #[Test]
    public function sendsNonMatchingVersionToDefault(): void
    {
        $rules = new BranchRules(
            ['PAY 5.0.0', 'PAY 5.0.1'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('PAY 5.0.0'),
            'patch-0 version must fall through to default when only beta rule exists',
        );
    }

    #[Test]
    public function mapsStageByTemplateInterpolation(): void
    {
        $rules = new BranchRules(
            ['CORE 9.2.1', 'CORE 9.3.0', 'CORE 10.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'stage',
            $rules->branchFor('CORE 9.3.0'),
            'version matching interpolated template must map to stage',
        );
    }

    #[Test]
    public function picksMaxBetaByDefaultSort(): void
    {
        $rules = new BranchRules(
            ['APP 2.0.1', 'APP 3.0.1', 'APP 4.0.1'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('APP 4.0.1'),
            'default sort desc must pick maximum among beta candidates',
        );
    }

    #[Test]
    public function picksMaxBetaWithExplicitSortDesc(): void
    {
        $rules = new BranchRules(
            ['GATE 7.1.1', 'GATE 8.2.1', 'GATE 9.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/', 'sort' => 'desc'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('GATE 8.2.1'),
            'explicit sort desc must pick maximum among beta candidates',
        );
    }

    #[Test]
    public function sendsNonMaxBetaCandidateToDefault(): void
    {
        $rules = new BranchRules(
            ['APP 2.0.1', 'APP 3.0.1', 'APP 4.0.1'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('APP 2.0.1'),
            'non-max beta candidate must fall through to default',
        );
    }

    #[Test]
    public function picksMinBetaWhenSortAsc(): void
    {
        $rules = new BranchRules(
            ['SVC 1.1.1', 'SVC 2.1.1', 'SVC 3.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/', 'sort' => 'asc'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('SVC 1.1.1'),
            'sort asc must pick minimum among beta candidates',
        );
    }

    #[Test]
    public function sendsNonMinBetaCandidateToDefaultWhenAsc(): void
    {
        $rules = new BranchRules(
            ['SVC 1.1.1', 'SVC 2.1.1', 'SVC 3.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/', 'sort' => 'asc'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('SVC 2.1.1'),
            'non-min beta candidate must fall through to default when sort asc',
        );
    }

    #[Test]
    public function skipsTemplateRuleWhenNoVarsResolved(): void
    {
        $rules = new BranchRules(
            ['DATA 6.0.0', 'DATA 6.1.0'],
            [
                'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('DATA 6.1.0'),
            'template rule without preceding regex must not resolve vars',
        );
    }

    #[Test]
    public function matchesHighPatchNumber(): void
    {
        $rules = new BranchRules(
            ['HUB 20.5.1', 'HUB 20.6.0', 'HUB 21.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('HUB 20.5.1'),
            'high semver numbers must match correctly',
        );
    }

    #[Test]
    public function mapsAllToDefaultWhenRuleMatchesNone(): void
    {
        $rules = new BranchRules(
            ['OPS 4.0.0', 'OPS 4.2.0', 'OPS 5.0.0'],
            [
                'beta' => ['match' => '/\.99$/'],
                'default' => 'release',
            ],
        );

        self::assertSame(
            'release',
            $rules->branchFor('OPS 4.2.0'),
            'impossible regex must leave all versions on default',
        );
    }

    #[Test]
    public function handlesMultipleBranchRules(): void
    {
        $rules = new BranchRules(
            ['FIN 3.0.1', 'FIN 3.1.0', 'FIN 4.0.0', 'FIN 4.0.2'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                'hotfix' => ['match' => '/\.2$/'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'hotfix',
            $rules->branchFor('FIN 4.0.2'),
            'third rule must match independently of beta/stage',
        );
    }

    #[Test]
    public function throwsForUnknownVersion(): void
    {
        $rules = new BranchRules(
            ['CRM 2.0.0'],
            ['default' => 'dev'],
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('not found among active releases');

        $rules->branchFor('CRM 99.0.0');
    }

    #[Test]
    public function throwsForInvalidRegex(): void
    {
        $rules = new BranchRules(
            ['LOG 1.0.0'],
            ['beta' => ['match' => '/[unclosed'], 'default' => 'dev'],
        );

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('Invalid branch-rule regex');

        $rules->branchFor('LOG 1.0.0');
    }

    #[Test]
    public function ignoresNonArrayRuleEntries(): void
    {
        $rules = new BranchRules(
            ['NET 1.0.0'],
            [
                'beta' => 'not-an-array',
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('NET 1.0.0'),
            'non-array rule entry must be silently skipped',
        );
    }

    #[Test]
    public function ignoresMissingMatchKey(): void
    {
        $rules = new BranchRules(
            ['SEC 2.0.0'],
            [
                'beta' => ['sort' => 'desc'],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('SEC 2.0.0'),
            'rule without match key must not crash',
        );
    }

    #[Test]
    public function treatsNonStringSortAsDesc(): void
    {
        $rules = new BranchRules(
            ['FLOW 1.0.1', 'FLOW 5.0.1', 'FLOW 6.0.0'],
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/', 'sort' => 99],
                'default' => 'dev',
            ],
        );

        self::assertSame(
            'beta',
            $rules->branchFor('FLOW 5.0.1'),
            'non-string sort must fall back to desc behavior',
        );
    }

    #[Test]
    public function usesDevWhenDefaultIsNonString(): void
    {
        $rules = new BranchRules(
            ['API 1.0.0'],
            ['default' => 42],
        );

        self::assertSame(
            'dev',
            $rules->branchFor('API 1.0.0'),
            'non-string default must fall back to dev',
        );
    }

    #[Test]
    public function neverProducesMasterWithStandardRules(): void
    {
        $versions = ['DOCK 3.0.0', 'DOCK 3.0.1', 'DOCK 3.1.0', 'DOCK 4.0.0'];
        $rules = new BranchRules(
            $versions,
            [
                'beta' => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
                'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
                'default' => 'dev',
            ],
        );

        $branches = array_map(
            static fn(string $v): string => $rules->branchFor($v),
            $versions,
        );

        self::assertNotContains(
            'master',
            $branches,
            'standard rules must never produce master branch',
        );
    }
}
