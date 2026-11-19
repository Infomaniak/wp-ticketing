<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Boots the plugin and wires its services to WordPress hooks.
 */
final class Plugin
{
    private static bool $booted = false;

    private function __construct() {}

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        add_action('init', [new Shortcode, 'register']);
        add_action('init', [new I18n, 'loadTextdomain']);
        add_action('wp_enqueue_scripts', [new Assets, 'register']);
        add_action('admin_menu', [new AdminPage, 'register']);
        add_action('admin_post_itkt_connect', [new AdminPage, 'handleConnect']);
        add_action('admin_post_itkt_disconnect', [new AdminPage, 'handleDisconnect']);
        add_action('admin_post_itkt_oauth_save_config', [new AdminPage, 'handleOAuthSaveConfig']);
        add_action('admin_post_itkt_oauth_connect', [new AdminPage, 'handleOAuthConnect']);
        add_action('wp_ajax_itkt_oauth_callback', [new AdminPage, 'handleOAuthCallback']);
    }
}
