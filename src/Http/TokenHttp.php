<?php

declare(strict_types=1);

namespace Goblin\Http;

use CurlHandle;
use Goblin\GoblinException;
use Override;

/**
 * HTTP client using curl with token-based auth header.
 *
 * @psalm-api
 */
final readonly class TokenHttp implements Http
{
    private const int HTTP_OK = 200;

    private const int HTTP_MULTIPLE_CHOICES = 300;

    private const int CONNECT_TIMEOUT_SECONDS = 10;

    private const int TIMEOUT_SECONDS = 30;

    /**
     * Configures base URL, auth header name and token value.
     *
     * @param string $url Endpoint URL.
     * @param string $header HTTP header name.
     * @param string $token Authentication token.
     */
    public function __construct(private string $url, private string $header, private string $token) {}

    #[Override]
    public function json(string $method, string $path, array $body = []): array
    {
        $curl = curl_init(sprintf('%s%s', $this->url, $path));

        if (!$curl instanceof CurlHandle) {
            throw new GoblinException("Failed to init curl for {$path}");
        }

        $this->configure($curl, $method, $body);
        $response = curl_exec($curl);
        $code = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        $error = curl_error($curl);
        unset($curl);

        if (!is_string($response)) {
            throw new GoblinException("Request failed: {$method} {$path} ({$error})");
        }

        if ($code < self::HTTP_OK || $code >= self::HTTP_MULTIPLE_CHOICES) {
            throw new GoblinException("HTTP {$code}: {$method} {$path}");
        }

        return $this->decoded($response, "{$method} {$path}");
    }

    /**
     * Sets curl options for method, headers and body.
     *
     * @param non-empty-string $method Method value.
     * @param array<string, mixed> $body Body value.
     * @throws GoblinException
     */
    private function configure(CurlHandle $curl, string $method, array $body): void
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            "{$this->header}: {$this->token}",
        ];

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
        ]);

        if ($body !== []) {
            $encoded = json_encode($body);

            if (!is_string($encoded)) {
                throw new GoblinException("Failed to encode body: {$method}");
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
        }
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
