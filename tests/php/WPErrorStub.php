<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

/**
 * Stands in for WP_Error, which is not loadable without WordPress.
 */
final class WPErrorStub
{
    public function get_error_message(): string
    {
        return 'cURL error 7: connection refused';
    }
}
