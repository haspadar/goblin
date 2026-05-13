<?php

declare(strict_types=1);

namespace Goblin\Http;

use CurlHandle;
use Goblin\GoblinException;
use Override;

/**
 * HTTP client using curl with Basic Auth.
 */
final readonly class CurlHttp implements Http
{
    private const int HTTP_BAD_REQUEST = 400;

    /**
     * Configures base URL and Basic Auth credentials.
     *
     * @param string $url Endpoint URL.
     * @param string $user Username for authentication.
     * @param string $token Authentication token.
     */
    public function __construct(private string $url, private string $user, private string $token) {}

    #[Override]
    public function json(string $method, string $path, array $body = []): array
    {
        $curl = curl_init(sprintf('%s%s', $this->url, $path));

        if (!$curl instanceof CurlHandle) {
            throw new GoblinException("Failed to init curl for {$path}");
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_USERPWD => "{$this->user}:{$this->token}",
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($body !== []) {
            $encoded = json_encode($body);

            if (!is_string($encoded)) {
                throw new GoblinException("Failed to encode body: {$method} {$path}");
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
        }

        $response = curl_exec($curl);
        $code = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        unset($curl);

        if (!is_string($response)) {
            throw new GoblinException("Request failed: {$method} {$path}");
        }

        if ($code >= self::HTTP_BAD_REQUEST) {
            throw new GoblinException("HTTP {$code}: {$method} {$path}");
        }

        return $this->decoded($response, "{$method} {$path}");
    }

    /**
     * Decodes a JSON response string into an array.
     *
     * @throws GoblinException
     * @return array<string, mixed>
     */
    private function decoded(string $response, string $label): array
    {
        if ($response === '') {
            return [];
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new GoblinException("Invalid JSON response: {$label}");
        }

        /** @phpstan-var array<string, mixed> $decoded */
        return $decoded;
    }
}
