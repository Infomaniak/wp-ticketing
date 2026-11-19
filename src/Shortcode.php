<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Registers and renders the ticketing shortcode.
 */
final class Shortcode
{
    public const TAG = 'infomaniak_ticketing';

    public const MOUNT_ID = 'itkt-app';

    public function register(): void
    {
        add_shortcode(self::TAG, [self::class, 'render']);
    }

    public function render(): string
    {
        wp_enqueue_style(Assets::HANDLE);
        wp_enqueue_script(Assets::HANDLE);

        return sprintf('<div id="%s"></div>', esc_attr(self::MOUNT_ID));
    }
}
