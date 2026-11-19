<?php

/**
 * Plugin Name:       Infomaniak Ticketing
 * Plugin URI:        https://gitlab.infomaniak.ch/infomaniak/wp-infomaniak-ticketing
 * Description:       The official Infomaniak WordPress plugin, built for organisers already using Infomaniak Ticketing. Easily add your events to your site, sell tickets online and offer a smooth purchasing experience, with no development required.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Infomaniak
 * Author URI:        https://www.infomaniak.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-infomaniak-ticketing
 * Domain Path:       /languages
 */

declare(strict_types=1);
use Infomaniak\Ticketing\Plugin;

defined('ABSPATH') || exit;

// Keep WP_INFOMANIAK_TICKETING_VERSION in sync with the "Version" header above.
define('WP_INFOMANIAK_TICKETING_VERSION', '0.1.0');
define('WP_INFOMANIAK_TICKETING_FILE', __FILE__);
define('WP_INFOMANIAK_TICKETING_DIR', __DIR__);
define('WP_INFOMANIAK_TICKETING_URL', plugins_url('', __FILE__));

if (! file_exists(WP_INFOMANIAK_TICKETING_DIR.'/vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        if (! current_user_can('install_plugins')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'Infomaniak Ticketing: run "composer install" in the plugin folder to load its dependencies.',
                'wp-infomaniak-ticketing',
            ),
        );
    });

    return;
}

require_once WP_INFOMANIAK_TICKETING_DIR.'/vendor/autoload.php';

Plugin::boot();
