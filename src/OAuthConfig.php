<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * OAuth2 client configuration, stored in the options table.
 *
 * The redirect URI is derived from the current site URL (admin-ajax.php), so
 * every WordPress site registers its own callback in its own Infomaniak
 * OAuth application — the same pattern as the official Infomaniak Connect
 * OpenID plugin.
 */
final class OAuthConfig
{
    public const CALLBACK_ACTION = 'itkt_oauth_callback';

    public const DEFAULT_SCOPES = 'openid profile email';

    public const MANAGER_APPS_URL = 'https://manager.infomaniak.com/v3/ng/accounts/applications/list';

    private const OPTION_NAME = 'itkt_oauth_client';

    public static function isConfigured(): bool
    {
        return self::clientId() !== '';
    }

    public static function clientId(): string
    {
        return (string) get_option('itkt_oauth_client_id', '');
    }

    public static function clientSecret(): string
    {
        return (string) get_option('itkt_oauth_client_secret', '');
    }

    public static function scopes(): string
    {
        $scopes = (string) get_option('itkt_oauth_client_scopes', '');

        return $scopes !== '' ? $scopes : self::DEFAULT_SCOPES;
    }

    public static function apiKey(): string
    {
        return (string) get_option('itkt_oauth_client_api_key', '');
    }

    public static function redirectUri(): string
    {
        return admin_url('admin-ajax.php?action='.self::CALLBACK_ACTION);
    }

    public static function save(string $clientId, string $clientSecret, string $scopes, string $apiKey): void
    {
        update_option('itkt_oauth_client_id', $clientId);
        update_option('itkt_oauth_client_secret', $clientSecret);
        update_option('itkt_oauth_client_scopes', $scopes);
        update_option('itkt_oauth_client_api_key', $apiKey);
    }
}
