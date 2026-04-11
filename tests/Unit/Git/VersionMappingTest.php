<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Git;

use Goblin\Git\VersionMapping;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VersionMappingTest extends TestCase
{
    #[Test]
    public function mapsAllToMasterWhenNoBetas(): void
    {
        $mapping = new VersionMapping(['PROJ 14.0.0', 'PROJ 15.0.0']);

        self::assertSame(
            'master',
            $mapping->branchFor('PROJ 14.0.0'),
            'without betas all versions must map to master',
        );
    }

    #[Test]
    public function mapsBetaToBeta(): void
    {
        $mapping = new VersionMapping(['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 15.0.0']);

        self::assertSame(
            'beta',
            $mapping->branchFor('PROJ 14.0.1'),
            'X.Y.1 version must map to beta',
        );
    }

    #[Test]
    public function mapsAboveBetaToDev(): void
    {
        $mapping = new VersionMapping(['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 15.0.0']);

        self::assertSame(
            'dev',
            $mapping->branchFor('PROJ 15.0.0'),
            'version above beta must map to dev',
        );
    }

    #[Test]
    public function mapsBelowBetaToMaster(): void
    {
        $mapping = new VersionMapping(['PROJ 14.0.0', 'PROJ 14.0.1', 'PROJ 15.0.0']);

        self::assertSame(
            'master',
            $mapping->branchFor('PROJ 14.0.0'),
            'version below beta must map to master',
        );
    }

    #[Test]
    public function mapsStageToStageWithTwoBetas(): void
    {
        $mapping = new VersionMapping([
            'PROJ 14.0.0',
            'PROJ 14.0.1',
            'PROJ 15.0.0',
            'PROJ 15.0.1',
        ]);

        self::assertSame(
            'stage',
            $mapping->branchFor('PROJ 15.0.0'),
            'stage version (X.Y.0 of latest beta) must map to stage',
        );
    }

    #[Test]
    public function throwsForUnknownVersion(): void
    {
        $mapping = new VersionMapping(['PROJ 14.0.0']);

        $this->expectException(GoblinException::class);
        $this->expectExceptionMessage('not found among active releases');

        $mapping->branchFor('PROJ 99.0.0');
    }
}
