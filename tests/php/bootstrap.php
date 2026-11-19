<?php

declare(strict_types=1);

// PHPUnit bootstrap: Brain Monkey stubs the WordPress API in-memory, no WP install needed.

require_once __DIR__.'/../../vendor/autoload.php';

// Constants normally defined by the main plugin file; unit tests boot the plugin
// classes directly, so define them here first.
if (! defined('WP_INFOMANIAK_TICKETING_VERSION')) {
    define('WP_INFOMANIAK_TICKETING_VERSION', '0.1.0');
    define('WP_INFOMANIAK_TICKETING_FILE', __DIR__.'/../../wp-infomaniak-ticketing.php');
    define('WP_INFOMANIAK_TICKETING_DIR', dirname(WP_INFOMANIAK_TICKETING_FILE));
    define('WP_INFOMANIAK_TICKETING_URL', 'https://example.org/wp-content/plugins/wp-infomaniak-ticketing');
}
