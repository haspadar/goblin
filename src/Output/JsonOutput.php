<?php

declare(strict_types=1);

namespace Goblin\Output;

use Override;

/**
 * Structured JSON output for non-interactive contexts.
 */
final readonly class JsonOutput implements Output
{
    /**
     * Accepts stdout and stderr streams.
     *
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(private mixed $stdout = STDOUT, private mixed $stderr = STDERR) {}

    #[Override]
    public function info(string $text): void
    {
        $this->write($this->stdout, 'info', $text);
    }

    #[Override]
    public function success(string $text): void
    {
        $this->write($this->stdout, 'success', $text);
    }

    #[Override]
    public function error(string $text): void
    {
        $this->write($this->stderr, 'error', $text);
    }

    #[Override]
    public function muted(string $text): void
    {
        $this->write($this->stdout, 'muted', $text);
    }

    /**
     * Writes a JSON line to the given stream.
     *
     * @param resource $stream
     */
    private function write(mixed $stream, string $level, string $text): void
    {
        $json = json_encode(
            ['level' => $level, 'message' => $text],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        assert(is_string($json));
        fwrite($stream, $json . PHP_EOL);
    }
}
