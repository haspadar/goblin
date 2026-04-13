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
        $debug = new DebugOutput($fake, $this->nullStream());

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
        $debug = new DebugOutput($fake, $this->nullStream());

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
        $debug = new DebugOutput($fake, $this->nullStream());

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
        $debug = new DebugOutput($fake, $this->nullStream());

        $debug->muted('cache warmed');

        self::assertSame(
            'cache warmed',
            $fake->muted[0] ?? '',
            'muted must be delegated to wrapped output',
        );
    }

    #[Test]
    public function writesTimestampedDebugLineToStderr(): void
    {
        $fake = new FakeOutput();
        $stderr = fopen('php://memory', 'rw');
        /** @var resource $stderr */
        $debug = new DebugOutput($fake, $stderr);

        $debug->info('deploying now');

        rewind($stderr);
        $line = (string) stream_get_contents($stderr);

        self::assertMatchesRegularExpression(
            '/^\[\d{2}:\d{2}:\d{2}\] \[info\] deploying now$/',
            trim($line),
            'debug must write timestamped line to stderr',
        );
    }

    /**
     * @return resource
     */
    private function nullStream(): mixed
    {
        /** @var resource */
        return fopen('php://memory', 'w');
    }
}
