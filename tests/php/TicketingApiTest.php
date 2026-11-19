<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\Credentials;
use Infomaniak\Ticketing\TicketingApi;
use PHPUnit\Framework\TestCase;

final class TicketingApiTest extends TestCase
{
    private Credentials $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->credentials = new Credentials('token-value', 'key-value');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_request_sends_bearer_and_key_headers(): void
    {
        $captured = [];
        Monkey\Functions\expect('wp_remote_request')->once()->andReturnUsing(function (string $url, array $args) use (&$captured) {
            $captured = ['url' => $url, 'args' => $args];

            return ['body' => '{}'];
        });
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn(200);

        TicketingApi::verify($this->credentials);

        $this->assertSame(TicketingApi::PROFILE_URL, $captured['url']);
        $this->assertSame('GET', $captured['args']['method']);
        $this->assertSame('Bearer token-value', $captured['args']['headers']['Authorization']);
        $this->assertSame('key-value', $captured['args']['headers']['key']);
    }

    public function test_verify_accepts_valid_credentials(): void
    {
        Monkey\Functions\expect('wp_remote_request')->once()->andReturn(['body' => '{}']);
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);

        $verification = TicketingApi::verify($this->credentials);

        $this->assertTrue($verification->isConnected());
        $this->assertSame('', $verification->errorMessage());
    }

    public function test_verify_rejects_invalid_credentials(): void
    {
        Monkey\Functions\expect('wp_remote_request')->once()->andReturn(['body' => '{}']);
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(401);

        $verification = TicketingApi::verify($this->credentials);

        $this->assertFalse($verification->isConnected());
        $this->assertSame('HTTP 401', $verification->errorMessage());
        $this->assertSame(401, $verification->statusCode());
    }

    public function test_verify_reports_network_failures(): void
    {
        Monkey\Functions\expect('wp_remote_request')->once()->andReturn(new WPErrorStub);
        Monkey\Functions\when('is_wp_error')->justReturn(true);

        $verification = TicketingApi::verify($this->credentials);

        $this->assertFalse($verification->isConnected());
        $this->assertSame('cURL error 7: connection refused', $verification->errorMessage());
        $this->assertSame(0, $verification->statusCode());
    }
}
