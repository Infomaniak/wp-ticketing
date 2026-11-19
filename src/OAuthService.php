<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * OAuth2 authorization code flow with PKCE (S256) against Infomaniak Connect.
 *
 * Public-client flow: no client secret is required (an optional one is sent
 * when configured). The code verifier is bound to a random state kept in a
 * transient, mirroring the official Infomaniak Connect OpenID plugin.
 */
final class OAuthService
{
    public const AUTHORIZE_URL = 'https://login.infomaniak.com/authorize';

    public const TOKEN_URL = 'https://login.infomaniak.com/token';

    private const STATE_TTL_SECONDS = 300;

    private const STATE_TRANSIENT_PREFIX = 'itkt_oauth_state--';

    public static function authorizationUrl(): string
    {
        $state = self::randomToken();
        $codeVerifier = self::randomToken();

        set_transient(self::STATE_TRANSIENT_PREFIX.$state, ['code_verifier' => $codeVerifier], self::STATE_TTL_SECONDS);

        return add_query_arg([
            'response_type' => 'code',
            'client_id' => OAuthConfig::clientId(),
            'redirect_uri' => OAuthConfig::redirectUri(),
            'scope' => OAuthConfig::scopes(),
            'state' => $state,
            'code_challenge' => self::codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ], self::AUTHORIZE_URL);
    }

    /**
     * Handles the admin-ajax callback and returns the redirect status:
     * connected, oauth_denied, oauth_invalid_state, oauth_token_exchange_failed
     * or oauth_verification_failed.
     *
     * @param  array<string, mixed>  $query  raw query parameters (GET)
     */
    public static function handleCallback(array $query): string
    {
        $error = $query['error'] ?? null;

        if (is_string($error) && $error !== '') {
            return 'oauth_denied';
        }

        $code = $query['code'] ?? null;
        $state = $query['state'] ?? null;

        if (! is_string($code) || $code === '' || ! is_string($state) || $state === '') {
            return 'oauth_invalid_state';
        }

        $codeVerifier = self::takeCodeVerifier($state);

        if ($codeVerifier === null) {
            return 'oauth_invalid_state';
        }

        $tokenResponse = self::requestToken($code, $codeVerifier);

        if ($tokenResponse === null) {
            return 'oauth_token_exchange_failed';
        }

        $credentials = new Credentials((string) $tokenResponse['access_token'], OAuthConfig::apiKey());

        if (! TicketingApi::verify($credentials)->isConnected()) {
            return 'oauth_verification_failed';
        }

        $expiresIn = is_int($tokenResponse['expires_in'] ?? null) ? $tokenResponse['expires_in'] : 0;
        $refreshToken = is_string($tokenResponse['refresh_token'] ?? null) ? $tokenResponse['refresh_token'] : '';
        Connection::saveOAuth($credentials, $refreshToken, time() + $expiresIn);

        return 'connected';
    }

    /**
     * PKCE S256 code challenge (RFC 7636).
     */
    public static function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>|null decoded token payload, null on failure
     */
    private static function requestToken(string $code, string $codeVerifier): ?array
    {
        $body = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => OAuthConfig::redirectUri(),
            'client_id' => OAuthConfig::clientId(),
            'code_verifier' => $codeVerifier,
        ];

        $clientSecret = OAuthConfig::clientSecret();

        if ($clientSecret !== '') {
            $body['client_secret'] = $clientSecret;
        }

        $response = wp_remote_post(self::TOKEN_URL, [
            'timeout' => 15,
            'body' => $body,
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);

        $accessToken = is_array($payload) ? ($payload['access_token'] ?? null) : null;

        if (! is_string($accessToken) || $accessToken === '') {
            return null;
        }

        return $payload;
    }

    /**
     * Returns the stored code verifier for a state and invalidates the state.
     */
    private static function takeCodeVerifier(string $state): ?string
    {
        $stored = get_transient(self::STATE_TRANSIENT_PREFIX.$state);
        delete_transient(self::STATE_TRANSIENT_PREFIX.$state);

        $codeVerifier = is_array($stored) ? ($stored['code_verifier'] ?? null) : null;

        return is_string($codeVerifier) && $codeVerifier !== '' ? $codeVerifier : null;
    }

    private static function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
