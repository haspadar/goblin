<?php

declare(strict_types=1);

namespace Goblin\Http;

use Goblin\GoblinException;

/**
 * HTTP client for JSON API requests.
 *
 * @psalm-api
 */
interface Http
{
    /**
     * Sends a JSON request and returns the decoded response.
     *
     * @param non-empty-string $method
     * @param array<string, mixed> $body
     * @throws GoblinException
     * @return array<string, mixed>
     */
    public function json(string $method, string $path, array $body = []): array;
}
