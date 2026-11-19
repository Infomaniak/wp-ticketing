<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\OAuthConfig;
use Infomaniak\Ticketing\OAuthService;
use PHPUnit\Framework\TestCase;

final class OAuthServiceTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * @var array<string, mixed>
     */
    private array $transients = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->options = [];
        $this->transients = [];
        $this->stubOptions();
        Monkey\Functions\when('admin_url')->returnArg(1);
        Monkey\Functions\expect('set_transient')->zeroOrMoreTimes()->andReturnUsing(function (string $name, mixed $value) {
            $this->transients[$name] = $value;

            return true;
        });
        Monkey\Functions\expect('get_transient')->zeroOrMoreTimes()->andReturnUsing(function (string $name) {
            return $this->transients[$name] ?? false;
        });
        Monkey\Functions\expect('delete_transient')->zeroOrMoreTimes()->andReturnUsing(function (string $name) {
            unset($this->transients[$name]);

            return true;
        });
        Monkey\Functions\expect('add_query_arg')->zeroOrMoreTimes()->andReturnUsing(static function (array $args, string $url): string {
            return $url.'?'.http_build_query($args);
        });
        Monkey\Functions\when('is_wp_error')->justReturn(false);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_code_challenge_matches_rfc_7636_vector(): void
    {
        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', OAuthService::codeChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'));
    }

    public function test_authorization_url_carries_pkce_and_state(): void
    {
        $this->configureOAuthClient();

        $url = OAuthService::authorizationUrl();

        $baseUrl = substr($url, 0, (int) strpos($url, '?'));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $state = (string) $query['state'];

        $this->assertSame(OAuthService::AUTHORIZE_URL, $baseUrl);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('client-id-value', $query['client_id']);
        $this->assertSame('admin-ajax.php?action=itkt_oauth_callback', $query['redirect_uri']);
        $this->assertSame(OAuthConfig::DEFAULT_SCOPES, $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(43, strlen((string) $query['code_challenge']));
        $this->assertSame(43, strlen($state));
        $this->assertSame($query['code_challenge'], OAuthService::codeChallenge((string) $this->transients['itkt_oauth_state--'.$state]['code_verifier']));
    }

    public function test_callback_connects_with_valid_code(): void
    {
        $this->configureOAuthClient();
        $this->transients['itkt_oauth_state--state-value'] = ['code_verifier' => 'verifier-value'];

        $tokenBody = json_encode([
            'access_token' => 'access-token-value',
            'refresh_token' => 'refresh-token-value',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
        $capturedTokenRequest = [];
        Monkey\Functions\expect('wp_remote_post')->once()->andReturnUsing(function (string $url, array $args) use (&$capturedTokenRequest, $tokenBody) {
            $capturedTokenRequest = ['url' => $url, 'args' => $args];

            return ['body' => $tokenBody];
        });
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Monkey\Functions\when('wp_remote_retrieve_body')->justReturn((string) $tokenBody);
        Monkey\Functions\expect('wp_remote_request')->zeroOrMoreTimes()->andReturn(['body' => '{}']);

        $status = OAuthService::handleCallback(['code' => 'auth-code', 'state' => 'state-value']);

        $this->assertSame('connected', $status);
        $this->assertSame(OAuthService::TOKEN_URL, $capturedTokenRequest['url']);
        $this->assertSame('authorization_code', $capturedTokenRequest['args']['body']['grant_type']);
        $this->assertSame('auth-code', $capturedTokenRequest['args']['body']['code']);
        $this->assertSame('verifier-value', $capturedTokenRequest['args']['body']['code_verifier']);
        $this->assertSame('client-id-value', $capturedTokenRequest['args']['body']['client_id']);
        $stored = $this->options['itkt_connection'];
        $this->assertSame('oauth', $stored['mode']);
        $this->assertSame('access-token-value', $stored['api_token']);
        $this->assertSame('refresh-token-value', $stored['refresh_token']);
        $this->assertGreaterThan(time(), $stored['expires_at']);
        $this->assertArrayNotHasKey('itkt_oauth_state--state-value', $this->transients, 'state must be single-use');
    }

    public function test_callback_rejects_unknown_state(): void
    {
        $status = OAuthService::handleCallback(['code' => 'auth-code', 'state' => 'unknown-state']);

        $this->assertSame('oauth_invalid_state', $status);
        $this->assertArrayNotHasKey('itkt_connection', $this->options);
    }

    public function test_callback_reports_denied_authorization(): void
    {
        $status = OAuthService::handleCallback(['error' => 'access_denied', 'state' => 'state-value']);

        $this->assertSame('oauth_denied', $status);
    }

    public function test_callback_reports_failed_token_exchange(): void
    {
        $this->configureOAuthClient();
        $this->transients['itkt_oauth_state--state-value'] = ['code_verifier' => 'verifier-value'];
        Monkey\Functions\expect('wp_remote_post')->once()->andReturn(['body' => '{"error":"invalid_grant"}']);
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn(400);

        $status = OAuthService::handleCallback(['code' => 'auth-code', 'state' => 'state-value']);

        $this->assertSame('oauth_token_exchange_failed', $status);
    }

    private function configureOAuthClient(): void
    {
        $this->options['itkt_oauth_client_id'] = 'client-id-value';
        $this->options['itkt_oauth_client_secret'] = '';
        $this->options['itkt_oauth_client_scopes'] = '';
        $this->options['itkt_oauth_client_api_key'] = 'key-value';
    }

    private function stubOptions(): void
    {
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
}
