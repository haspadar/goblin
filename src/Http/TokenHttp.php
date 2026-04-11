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
    /**
     * Configures base URL, auth header name and token value.
     */
    public function __construct(private string $url, private string $header, private string $token) {}

    #[Override]
    public function json(string $method, string $path, array $body = []): array
    {
        $curl = curl_init($this->url . $path);

        if ($curl === false) {
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

        if ($code >= 400) {
            throw new GoblinException("HTTP {$code}: {$method} {$path}");
        }

        return $this->decoded($response, "{$method} {$path}");
    }

    /**
     * Sets curl options for method, headers and body.
     *
     * @param non-empty-string $method
     * @param array<string, mixed> $body
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
        ]);

        if ($body !== []) {
            $encoded = json_encode($body);

            if ($encoded === false) {
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
