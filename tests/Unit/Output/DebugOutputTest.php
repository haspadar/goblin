<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Output;

use Goblin\Output\DebugOutput;
use Goblin\Tests\Fake\FakeOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DebugOutputTest extends TestCase
{
    #[Test]
    public function delegatesInfoToOrigin(): void
    {
        $fake = new FakeOutput();
        $debug = new DebugOutput($fake);

        $debug->info('migration running');

        self::assertSame(
            'migration running',
            $fake->infos[0] ?? '',
            'info must be delegated to wrapped output',
        );
    }

    #[Test]
    public function delegatesSuccessToOrigin(): void
    {
        $fake = new FakeOutput();
        $debug = new DebugOutput($fake);

        $debug->success('seeds planted');

        self::assertSame(
            'seeds planted',
            $fake->successes[0] ?? '',
            'success must be delegated to wrapped output',
        );
    }

    #[Test]
    public function delegatesErrorToOrigin(): void
    {
        $fake = new FakeOutput();
        $debug = new DebugOutput($fake);

        $debug->error('connection refused');

        self::assertSame(
            'connection refused',
            $fake->errors[0] ?? '',
            'error must be delegated to wrapped output',
        );
    }

    #[Test]
    public function delegatesMutedToOrigin(): void
    {
        $fake = new FakeOutput();
        $debug = new DebugOutput($fake);

        $debug->muted('cache warmed');

        self::assertSame(
            'cache warmed',
            $fake->muted[0] ?? '',
            'muted must be delegated to wrapped output',
        );
    }
}
