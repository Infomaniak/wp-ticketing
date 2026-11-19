<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * HTTP client for the Infomaniak Ticketing REST API.
 *
 * Every request carries the two credentials documented by the ticketing API:
 * `Authorization: Bearer <Manager token>` and `key: <ticketing API key>`.
 */
final class TicketingApi
{
    /**
     * Publicly documented endpoint, used to validate the Manager token.
     */
    public const PROFILE_URL = 'https://api.infomaniak.com/1/profile';

    private const TIMEOUT_SECONDS = 15;

    public static function verify(Credentials $credentials): VerificationResult
    {
        $response = self::request($credentials, 'GET', self::PROFILE_URL);

        if (is_wp_error($response)) {
            return new VerificationResult(false, (string) $response->get_error_message());
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);

        if ($statusCode === 200) {
            return new VerificationResult(true, '', $statusCode);
        }

        return new VerificationResult(false, sprintf('HTTP %d', $statusCode), $statusCode);
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public static function request(Credentials $credentials, string $method, string $url): array|object
    {
        return wp_remote_request($url, [
            'method' => $method,
            'timeout' => self::TIMEOUT_SECONDS,
            'headers' => [
                'Authorization' => 'Bearer '.$credentials->apiToken(),
                'key' => $credentials->apiKey(),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
