<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Admin page: plugin onboarding and Infomaniak account connection.
 *
 * State changes go through admin-post.php handlers (nonce + capability check
 * on every request); the page itself is a dumb renderer that reads the
 * connection state and the ?itkt_status redirect argument. The OAuth callback
 * lands on admin-ajax.php and follows the same redirect pattern.
 */
final class AdminPage
{
    public const PAGE_SLUG = 'itkt-ticketing';

    private const NONCE_ACTION = 'itkt_connect';

    private const NONCE_NAME = '_itkt_nonce';

    private const MANAGER_TOKEN_URL = 'https://manager.infomaniak.com/v3/ng/accounts/token/list';

    private const TICKETING_KEY_FAQ_URL = 'https://www.infomaniak.com/en/support/faq/2661';

    private const FAQ_URL = 'https://faq.infomaniak.com';

    public function register(): void
    {
        add_menu_page(
            esc_html__('Infomaniak Ticketing', 'wp-infomaniak-ticketing'),
            esc_html__('Ticketing', 'wp-infomaniak-ticketing'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
            'dashicons-tickets-alt',
            25,
        );
    }

    /**
     * Handles the manual connect form submission (admin-post.php).
     */
    public function handleConnect(): void
    {
        $this->assertCanManageOptions();
        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

        $status = self::saveConnectionFromPost($_POST);

        self::redirectWithStatus($status);
    }

    /**
     * Handles the OAuth application settings form submission (admin-post.php).
     */
    public function handleOAuthSaveConfig(): void
    {
        $this->assertCanManageOptions();
        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

        $status = self::saveOAuthConfigFromPost($_POST);

        self::redirectWithStatus($status);
    }

    /**
     * Starts the OAuth authorization request (admin-post.php): redirects the
     * admin to the Infomaniak Connect authorization endpoint.
     */
    public function handleOAuthConnect(): void
    {
        $this->assertCanManageOptions();
        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

        if (! OAuthConfig::isConfigured()) {
            self::redirectWithStatus('oauth_config_missing');
        }

        // wp_safe_redirect only allows internal hosts; the authorization
        // endpoint is the configured Infomaniak IDP, so the raw redirect is
        // intentional here.
        wp_redirect(OAuthService::authorizationUrl());
        exit;
    }

    /**
     * Handles the OAuth callback (admin-ajax.php?action=itkt_oauth_callback).
     */
    public function handleOAuthCallback(): void
    {
        $this->assertCanManageOptions();

        $status = OAuthService::handleCallback($_GET);

        self::redirectWithStatus($status);
    }

    /**
     * Handles the disconnect form submission (admin-post.php).
     */
    public function handleDisconnect(): void
    {
        $this->assertCanManageOptions();
        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

        Connection::clear();

        self::redirectWithStatus('disconnected');
    }

    /**
     * Validates the submitted credentials, saves them and returns the
     * redirect status. Kept side-effect-light so the decision logic stays
     * unit-testable (redirect + exit live in redirectWithStatus()).
     *
     * @param  array<string, mixed>  $post  raw POST payload (slashed)
     * @return string one of: connected, invalid_input, invalid_token, network_error
     */
    public static function saveConnectionFromPost(array $post): string
    {
        $apiToken = isset($post['itkt_api_token']) ? sanitize_text_field((string) wp_unslash($post['itkt_api_token'])) : '';
        $apiKey = isset($post['itkt_api_key']) ? sanitize_text_field((string) wp_unslash($post['itkt_api_key'])) : '';

        if ($apiToken === '' || $apiKey === '') {
            return 'invalid_input';
        }

        $credentials = new Credentials($apiToken, $apiKey);
        $verification = TicketingApi::verify($credentials);

        if ($verification->isConnected()) {
            Connection::save($credentials);

            return 'connected';
        }

        $statusCode = $verification->statusCode();

        return $statusCode >= 400 && $statusCode < 500 ? 'invalid_token' : 'network_error';
    }

    /**
     * Validates and stores the OAuth application settings, returning the
     * redirect status: oauth_config_saved or oauth_config_invalid. Left-empty
     * secret and API key fields keep the previously stored values (secrets
     * are never rendered back into the form).
     *
     * @param  array<string, mixed>  $post  raw POST payload (slashed)
     */
    public static function saveOAuthConfigFromPost(array $post): string
    {
        $clientId = isset($post['itkt_oauth_client_id']) ? sanitize_text_field((string) wp_unslash($post['itkt_oauth_client_id'])) : '';
        $clientSecret = isset($post['itkt_oauth_client_secret']) ? sanitize_text_field((string) wp_unslash($post['itkt_oauth_client_secret'])) : '';
        $apiKey = isset($post['itkt_oauth_api_key']) ? sanitize_text_field((string) wp_unslash($post['itkt_oauth_api_key'])) : '';

        if ($clientId === '') {
            return 'oauth_config_invalid';
        }

        OAuthConfig::save(
            $clientId,
            $clientSecret !== '' ? $clientSecret : OAuthConfig::clientSecret(),
            OAuthConfig::scopes(),
            $apiKey !== '' ? $apiKey : OAuthConfig::apiKey(),
        );

        return 'oauth_config_saved';
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $status = isset($_GET['itkt_status']) ? sanitize_key((string) wp_unslash($_GET['itkt_status'])) : '';

        if ($status !== '') {
            printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', in_array($status, ['connected', 'oauth_config_saved'], true) ? 'success' : 'error', esc_html(self::statusMessage($status)));
        }

        echo '<div class="wrap">';

        printf('<h1>%s</h1>', esc_html__('Welcome to the Infomaniak Ticketing plugin', 'wp-infomaniak-ticketing'));

        printf(
            '<div class="notice notice-info"><p>%s <a href="%s" target="_blank" rel="noopener">%s</a></p></div>',
            esc_html__('Discover the answers to the most frequently asked questions about using the Infomaniak Ticketing plugin for WordPress.', 'wp-infomaniak-ticketing'),
            esc_url(self::FAQ_URL),
            esc_html__('See our FAQ', 'wp-infomaniak-ticketing'),
        );

        echo '<div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));">';

        self::renderConnectCard();
        self::renderEventsCard();
        self::renderIntegrateCard();

        echo '</div></div>';
    }

    private static function renderConnectCard(): void
    {
        echo '<div class="card">';
        printf('<h2>%s</h2>', esc_html__('Connect your Infomaniak account to activate the plugin', 'wp-infomaniak-ticketing'));
        echo '<p>'.esc_html__('Use your existing Infomaniak account associated with your ticket shops.', 'wp-infomaniak-ticketing').'</p>';

        if (Connection::isConnected()) {
            printf(
                '<p>✔ %s</p><form method="post" action="%s"><input type="hidden" name="action" value="itkt_disconnect" /><input type="hidden" name="%s" value="%s" /><button type="submit" class="button">%s</button></form>',
                esc_html__('Your account is connected.', 'wp-infomaniak-ticketing'),
                esc_url(admin_url('admin-post.php')),
                esc_attr(self::NONCE_NAME),
                esc_attr(wp_create_nonce(self::NONCE_ACTION)),
                esc_html__('Disconnect', 'wp-infomaniak-ticketing'),
            );

            echo '</div>';

            return;
        }

        self::renderOAuthSection();
        self::renderManualSection();

        echo '</div>';
    }

    private static function renderOAuthSection(): void
    {
        printf('<h3>%s</h3>', esc_html__('Infomaniak Connect (recommended)', 'wp-infomaniak-ticketing'));

        echo '<p>'.esc_html__('1. Register the callback URL in your Infomaniak application', 'wp-infomaniak-ticketing').'</p>';
        echo '<p>'.esc_html__('Copy this URL into the "Redirect URI" field of your Infomaniak OAuth application.', 'wp-infomaniak-ticketing').'</p>';

        printf(
            '<p><input type="text" readonly class="regular-text code" value="%s" onfocus="this.select()" /></p>',
            esc_attr(OAuthConfig::redirectUri()),
        );

        printf(
            '<p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
            esc_url(OAuthConfig::MANAGER_APPS_URL),
            esc_html__('Manage your OAuth applications', 'wp-infomaniak-ticketing'),
        );

        echo '<p>'.esc_html__('2. Fill in your application credentials', 'wp-infomaniak-ticketing').'</p>';

        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        printf('<input type="hidden" name="action" value="itkt_oauth_save_config" /><input type="hidden" name="%s" value="%s" />', esc_attr(self::NONCE_NAME), esc_attr(wp_create_nonce(self::NONCE_ACTION)));

        printf(
            '<p><label for="itkt_oauth_client_id">%s<br /><input type="text" id="itkt_oauth_client_id" name="itkt_oauth_client_id" class="regular-text" value="%s" autocomplete="off" /></label></p>',
            esc_html__('OAuth client ID', 'wp-infomaniak-ticketing'),
            esc_attr(OAuthConfig::clientId()),
        );

        printf(
            '<p><label for="itkt_oauth_client_secret">%s<br /><input type="password" id="itkt_oauth_client_secret" name="itkt_oauth_client_secret" class="regular-text" autocomplete="off" /></label></p>',
            esc_html__('OAuth client secret (optional)', 'wp-infomaniak-ticketing'),
        );

        printf(
            '<p><label for="itkt_oauth_api_key">%s<br /><input type="password" id="itkt_oauth_api_key" name="itkt_oauth_api_key" class="regular-text" autocomplete="off" /></label></p>',
            esc_html__('Ticketing API key', 'wp-infomaniak-ticketing'),
        );

        printf('<p><button type="submit" class="button">%s</button></p>', esc_html__('Save connection settings', 'wp-infomaniak-ticketing'));
        echo '</form>';

        if (OAuthConfig::isConfigured()) {
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            printf('<input type="hidden" name="action" value="itkt_oauth_connect" /><input type="hidden" name="%s" value="%s" />', esc_attr(self::NONCE_NAME), esc_attr(wp_create_nonce(self::NONCE_ACTION)));
            printf('<p><button type="submit" class="button button-primary">%s</button></p>', esc_html__('Connect with Infomaniak', 'wp-infomaniak-ticketing'));
            echo '</form>';
        }
    }

    private static function renderManualSection(): void
    {
        printf('<h3>%s</h3>', esc_html__('Manual configuration', 'wp-infomaniak-ticketing'));

        printf(
            '<form method="post" action="%s"><input type="hidden" name="action" value="itkt_connect" /><input type="hidden" name="%s" value="%s" />',
            esc_url(admin_url('admin-post.php')),
            esc_attr(self::NONCE_NAME),
            esc_attr(wp_create_nonce(self::NONCE_ACTION)),
        );

        printf(
            '<p><label for="itkt_api_token">%s<br /><input type="password" id="itkt_api_token" name="itkt_api_token" class="regular-text" autocomplete="off" /></label></p>',
            esc_html__('Manager API token', 'wp-infomaniak-ticketing'),
        );

        printf(
            '<p><label for="itkt_api_key">%s<br /><input type="password" id="itkt_api_key" name="itkt_api_key" class="regular-text" autocomplete="off" /></label></p>',
            esc_html__('Ticketing API key', 'wp-infomaniak-ticketing'),
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s</a> — <a href="%4$s" target="_blank" rel="noopener">%5$s</a></p>',
            esc_html__('Where do I find these values?', 'wp-infomaniak-ticketing'),
            esc_url(self::MANAGER_TOKEN_URL),
            esc_html__('Create a Manager API token', 'wp-infomaniak-ticketing'),
            esc_url(self::TICKETING_KEY_FAQ_URL),
            esc_html__('Create a Ticketing API key', 'wp-infomaniak-ticketing'),
        );

        printf('<p><button type="submit" class="button button-primary">%s</button></p>', esc_html__('Connect', 'wp-infomaniak-ticketing'));

        echo '</form>';
    }

    private static function renderEventsCard(): void
    {
        echo '<div class="card">';
        printf('<h2>%s</h2>', esc_html__('View your events', 'wp-infomaniak-ticketing'));
        echo '<p>'.esc_html__('The list of your events and shops will be available with the integration codes.', 'wp-infomaniak-ticketing').'</p>';
        echo '</div>';
    }

    private static function renderIntegrateCard(): void
    {
        echo '<div class="card">';
        printf('<h2>%s</h2>', esc_html__('Integrate your events into your pages', 'wp-infomaniak-ticketing'));
        echo '<p>'.esc_html__('Add the shortcode or use the WordPress block to add an event or a shop to a page.', 'wp-infomaniak-ticketing').'</p>';
        echo '<p><code>[infomaniak_ticketing]</code></p>';
        echo '</div>';
    }

    private static function statusMessage(string $status): string
    {
        return match ($status) {
            'connected' => esc_html__('Your Infomaniak account is now connected.', 'wp-infomaniak-ticketing'),
            'disconnected' => esc_html__('Your account has been disconnected.', 'wp-infomaniak-ticketing'),
            'oauth_config_saved' => esc_html__('Connection settings saved.', 'wp-infomaniak-ticketing'),
            'oauth_config_invalid' => esc_html__('Please fill in the OAuth client ID.', 'wp-infomaniak-ticketing'),
            'oauth_config_missing' => esc_html__('OAuth configuration is missing. Save your connection settings first.', 'wp-infomaniak-ticketing'),
            'oauth_denied' => esc_html__('Connection failed: the connection request was denied.', 'wp-infomaniak-ticketing'),
            'oauth_invalid_state' => esc_html__('Connection failed: invalid OAuth state. Start the connection again.', 'wp-infomaniak-ticketing'),
            'oauth_token_exchange_failed' => esc_html__('Connection failed: Infomaniak rejected the OAuth code exchange.', 'wp-infomaniak-ticketing'),
            'oauth_verification_failed' => esc_html__('Connection failed: the access token was rejected by the Infomaniak API.', 'wp-infomaniak-ticketing'),
            'invalid_input' => esc_html__('Please fill in the Manager API token and the Ticketing API key.', 'wp-infomaniak-ticketing'),
            'invalid_token' => esc_html__('Connection failed: the Manager API token was rejected. Check your token and API key, then try again.', 'wp-infomaniak-ticketing'),
            default => esc_html__('Connection failed: the Infomaniak API could not be reached. Try again later.', 'wp-infomaniak-ticketing'),
        };
    }

    private function assertCanManageOptions(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'wp-infomaniak-ticketing'));
        }
    }

    private static function redirectWithStatus(string $status): void
    {
        wp_safe_redirect(add_query_arg(
            ['page' => self::PAGE_SLUG, 'itkt_status' => $status],
            admin_url('admin.php'),
        ));
        exit;
    }
}
