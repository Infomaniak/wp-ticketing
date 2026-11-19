<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\Assets;
use Infomaniak\Ticketing\Shortcode;
use PHPUnit\Framework\TestCase;

final class ShortcodeTest extends TestCase
{
    /**
     * @var array<int, array{0: string, 1: mixed}>
     */
    private array $registeredShortcodes = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Monkey\Functions\when('esc_attr')->returnArg();

        $this->registeredShortcodes = [];
        Monkey\Functions\when('add_shortcode')->alias(function (string $tag, mixed $callback): void {
            $this->registeredShortcodes[] = [$tag, $callback];
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_register_adds_shortcode(): void
    {
        (new Shortcode)->register();

        $this->assertSame([[Shortcode::TAG, [Shortcode::class, 'render']]], $this->registeredShortcodes);
    }

    public function test_render_enqueues_assets_and_returns_mount_point(): void
    {
        Monkey\Functions\expect('wp_enqueue_style')->once()->with(Assets::HANDLE);
        Monkey\Functions\expect('wp_enqueue_script')->once()->with(Assets::HANDLE);

        $rendered = (new Shortcode)->render();

        $this->assertSame(sprintf('<div id="%s"></div>', Shortcode::MOUNT_ID), $rendered);
    }
}
