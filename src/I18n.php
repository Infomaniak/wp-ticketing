<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Registers the plugin text domain.
 */
final class I18n
{
    private const TEXT_DOMAIN = 'wp-infomaniak-ticketing';

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(plugin_basename(WP_INFOMANIAK_TICKETING_FILE)).'/languages');
    }
}
