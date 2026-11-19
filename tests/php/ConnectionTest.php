<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\Connection;
use Infomaniak\Ticketing\Credentials;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->options = [];
        Monkey\Functions\expect('get_option')->zeroOrMoreTimes()->andReturnUsing(function (string $name, mixed $default = false) {
            return $this->options[$name] ?? $default;
        });
        Monkey\Functions\expect('update_option')->zeroOrMoreTimes()->andReturnUsing(function (string $name, mixed $value) {
            $this->options[$name] = $value;

            return true;
        });
        Monkey\Functions\expect('delete_option')->zeroOrMoreTimes()->andReturnUsing(function (string $name) {
            unset($this->options[$name]);

            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_connection_starts_empty(): void
    {
        $this->assertFalse(Connection::isConnected());
        $this->assertNull(Connection::credentials());
    }

    public function test_save_stores_credentials_and_reports_connected(): void
    {
        $credentials = new Credentials('token-value', 'key-value');

        Connection::save($credentials);

        $this->assertTrue(Connection::isConnected());
        $this->assertSame(Connection::MODE_MANUAL, Connection::mode());
        $stored = Connection::credentials();
        $this->assertNotNull($stored);
        $this->assertSame('token-value', $stored->apiToken());
        $this->assertSame('key-value', $stored->apiKey());
    }

    public function test_save_oauth_stores_tokens_and_mode(): void
    {
        Connection::saveOAuth(new Credentials('access-token-value', 'key-value'), 'refresh-token-value', 1700000000);

        $this->assertTrue(Connection::isConnected());
        $this->assertSame(Connection::MODE_OAUTH, Connection::mode());
        $this->assertSame('refresh-token-value', Connection::refreshToken());
        $this->assertSame(1700000000, Connection::expiresAt());
        $stored = Connection::credentials();
        $this->assertNotNull($stored);
        $this->assertSame('access-token-value', $stored->apiToken());
    }

    public function test_clear_disconnects(): void
    {
        Connection::save(new Credentials('token-value', 'key-value'));
        Connection::clear();

        $this->assertFalse(Connection::isConnected());
        $this->assertNull(Connection::credentials());
    }

    public function test_is_connected_ignores_malformed_storage(): void
    {
        $this->options['itkt_connection'] = ['api_token' => '', 'api_key' => 'key'];
        $this->assertFalse(Connection::isConnected());

        $this->options['itkt_connection'] = ['api_token' => 'token'];
        $this->assertFalse(Connection::isConnected());

        $this->options['itkt_connection'] = 'garbage';
        $this->assertFalse(Connection::isConnected());
    }
}
