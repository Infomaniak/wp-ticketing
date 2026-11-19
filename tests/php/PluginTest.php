<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PluginTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $registeredHooks = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Reset the private static boot flag: it would otherwise leak between tests.
        $bootedFlag = new ReflectionProperty(Plugin::class, 'booted');
        $bootedFlag->setValue(null, false);

        $this->registeredHooks = [];
        Monkey\Functions\when('add_action')->alias(function (string $hook, mixed ...$args): void {
            $this->registeredHooks[] = $hook;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_boot_registers_plugin_hooks(): void
    {
        Plugin::boot();

        $this->assertSame(
            [
                'init',
                'init',
                'wp_enqueue_scripts',
                'admin_menu',
                'admin_post_itkt_connect',
                'admin_post_itkt_disconnect',
                'admin_post_itkt_oauth_save_config',
                'admin_post_itkt_oauth_connect',
                'wp_ajax_itkt_oauth_callback',
            ],
            $this->registeredHooks,
        );
    }

    public function test_boot_is_idempotent(): void
    {
        Plugin::boot();
        Plugin::boot();

        $this->assertSame(
            [
                'init',
                'init',
                'wp_enqueue_scripts',
                'admin_menu',
                'admin_post_itkt_connect',
                'admin_post_itkt_disconnect',
                'admin_post_itkt_oauth_save_config',
                'admin_post_itkt_oauth_connect',
                'wp_ajax_itkt_oauth_callback',
            ],
            $this->registeredHooks,
        );
    }
}
