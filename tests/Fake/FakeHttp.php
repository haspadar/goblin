<?php

declare(strict_types=1);

namespace Goblin\Tests\Fake;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Canned HTTP responses for tests
 */
final class FakeHttp implements Http
{
    /** @var array<string, mixed> */
    private array $lastBody = [];

    /**
     * @param array<string, array<string, mixed>> $responses "METHOD /path" => response
     */
    public function __construct(
        private readonly array $responses,
    ) {
    }

    /** @return array<string, mixed> */
    public function json(string $method, string $path, array $body = []): array
    {
        $this->lastBody = $body;

        $key = "{$method} {$path}";

        if (!array_key_exists($key, $this->responses)) {
            throw new GoblinException("Unexpected request: {$key}");
        }

        return $this->responses[$key];
    }

    /** @return array<string, mixed> */
    public function lastBody(): array
    {
        return $this->lastBody;
    }
}
