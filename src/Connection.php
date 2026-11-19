<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Stores the Infomaniak connection in the options table.
 *
 * Credentials never leave the server: they are written to a single prefixed
 * option and are never rendered in HTML or exposed to front-end JavaScript.
 *
 * Three connection modes are supported: manual (Manager API token + ticketing
 * API key), oauth (access token from the PKCE flow + refresh token) and
 * credential (Infomaniak Connect credential). The stored `api_token` value is
 * the authentication value for the current mode; the ticketing API key is
 * always kept alongside it.
 */
final class Connection
{
    public const MODE_MANUAL = 'manual';

    public const MODE_OAUTH = 'oauth';

    public const MODE_CREDENTIAL = 'credential';

    private const OPTION_NAME = 'itkt_connection';

    public static function isConnected(): bool
    {
        return self::credentials() instanceof Credentials;
    }

    public static function credentials(): ?Credentials
    {
        return self::credentialsFromStorage((array) get_option(self::OPTION_NAME, []));
    }

    /**
     * @return string one of: manual, oauth, credential, or an empty string
     */
    public static function mode(): string
    {
        $mode = (array) get_option(self::OPTION_NAME, []);
        $stored = is_array($mode) ? ($mode['mode'] ?? null) : null;

        return is_string($stored) && $stored !== '' ? $stored : self::MODE_MANUAL;
    }

    public static function refreshToken(): ?string
    {
        $storage = (array) get_option(self::OPTION_NAME, []);
        $refreshToken = is_array($storage) ? ($storage['refresh_token'] ?? null) : null;

        return is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null;
    }

    public static function expiresAt(): int
    {
        $storage = (array) get_option(self::OPTION_NAME, []);
        $expiresAt = is_array($storage) ? ($storage['expires_at'] ?? null) : null;

        return is_int($expiresAt) ? $expiresAt : 0;
    }

    public static function save(Credentials $credentials): void
    {
        self::write(self::MODE_MANUAL, $credentials, null, 0);
    }

    public static function saveOAuth(Credentials $credentials, string $refreshToken, int $expiresAt): void
    {
        self::write(self::MODE_OAUTH, $credentials, $refreshToken, $expiresAt);
    }

    public static function clear(): void
    {
        delete_option(self::OPTION_NAME);
    }

    private static function write(string $mode, Credentials $credentials, ?string $refreshToken, int $expiresAt): void
    {
        update_option(self::OPTION_NAME, [
            'mode' => $mode,
            'api_token' => $credentials->apiToken(),
            'api_key' => $credentials->apiKey(),
            'connected_at' => time(),
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $storage
     */
    private static function credentialsFromStorage(array $storage): ?Credentials
    {
        $apiToken = $storage['api_token'] ?? null;
        $apiKey = $storage['api_key'] ?? null;

        if (! is_string($apiToken) || $apiToken === '' || ! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        return new Credentials($apiToken, $apiKey);
    }
}
