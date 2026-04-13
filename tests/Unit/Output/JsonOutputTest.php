<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Output;

use Goblin\Output\JsonOutput;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonOutputTest extends TestCase
{
    #[Test]
    public function infoWritesJsonWithInfoLevel(): void
    {
        $stream = fopen('php://memory', 'rw');
        /** @var resource $stream */
        $output = new JsonOutput($stream);

        $output->info('deployment started');

        self::assertSame(
            ['level' => 'info', 'message' => 'deployment started'],
            $this->readJson($stream),
            'info must produce JSON with level=info',
        );
    }

    #[Test]
    public function successWritesJsonWithSuccessLevel(): void
    {
        $stream = fopen('php://memory', 'rw');
        /** @var resource $stream */
        $output = new JsonOutput($stream);

        $output->success('tests passed');

        self::assertSame(
            ['level' => 'success', 'message' => 'tests passed'],
            $this->readJson($stream),
            'success must produce JSON with level=success',
        );
    }

    #[Test]
    public function errorWritesJsonWithErrorLevel(): void
    {
        $stdout = fopen('php://memory', 'rw');
        $stderr = fopen('php://memory', 'rw');
        /** @var resource $stdout */
        /** @var resource $stderr */
        $output = new JsonOutput($stdout, $stderr);

        $output->error('connection refused');

        self::assertSame(
            ['level' => 'error', 'message' => 'connection refused'],
            $this->readJson($stderr),
            'error must produce JSON on stderr with level=error',
        );
    }

    #[Test]
    public function mutedWritesJsonWithMutedLevel(): void
    {
        $stream = fopen('php://memory', 'rw');
        /** @var resource $stream */
        $output = new JsonOutput($stream);

        $output->muted('skipped hook');

        self::assertSame(
            ['level' => 'muted', 'message' => 'skipped hook'],
            $this->readJson($stream),
            'muted must produce JSON with level=muted',
        );
    }

    #[Test]
    public function handlesInvalidUtf8WithoutBreakingJson(): void
    {
        $stream = fopen('php://memory', 'rw');
        /** @var resource $stream */
        $output = new JsonOutput($stream);

        $output->info("broken \xc3 bytes");

        $decoded = $this->readJson($stream);

        self::assertSame(
            'info',
            $decoded['level'] ?? '',
            'invalid UTF-8 must not break JSON structure',
        );
    }

    /**
     * @param resource $stream
     * @return array<string, mixed>|null
     */
    private function readJson(mixed $stream): array|null
    {
        rewind($stream);

        return json_decode(trim((string) stream_get_contents($stream)), true);
    }
}
