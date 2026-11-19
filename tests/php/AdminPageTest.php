<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing\Tests;

use Brain\Monkey;
use Infomaniak\Ticketing\AdminPage;
use Infomaniak\Ticketing\OAuthConfig;
use PHPUnit\Framework\TestCase;

final class AdminPageTest extends TestCase
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
        Monkey\Functions\when('wp_unslash')->returnArg(1);
        Monkey\Functions\when('sanitize_text_field')->returnArg(1);
        Monkey\Functions\when('sanitize_key')->returnArg(1);
        Monkey\Functions\when('esc_html')->returnArg(1);
        Monkey\Functions\when('esc_html__')->returnArg(1);
        Monkey\Functions\when('esc_attr')->returnArg(1);
        Monkey\Functions\when('esc_url')->returnArg(1);
        Monkey\Functions\when('admin_url')->returnArg(1);
        Monkey\Functions\when('wp_create_nonce')->justReturn('nonce');
        Monkey\Functions\when('current_user_can')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_register_adds_menu_page(): void
    {
        $registered = [];
        Monkey\Functions\expect('add_menu_page')->once()->andReturnUsing(function (mixed ...$args) use (&$registered): string {
            $registered = $args;

            return AdminPage::PAGE_SLUG;
        });

        (new AdminPage)->register();

        $this->assertSame('Infomaniak Ticketing', $registered[0]);
        $this->assertSame('Ticketing', $registered[1]);
        $this->assertSame('manage_options', $registered[2]);
        $this->assertSame(AdminPage::PAGE_SLUG, $registered[3]);
        $this->assertSame('dashicons-tickets-alt', $registered[5]);
    }

    public function test_save_connection_requires_both_credentials(): void
    {
        $this->assertSame('invalid_input', AdminPage::saveConnectionFromPost([]));
        $this->assertSame('invalid_input', AdminPage::saveConnectionFromPost(['itkt_api_token' => 'token']));
    }

    public function test_save_connection_stores_valid_credentials(): void
    {
        $this->stubSuccessfulVerify();

        $status = AdminPage::saveConnectionFromPost([
            'itkt_api_token' => 'token-value',
            'itkt_api_key' => 'key-value',
        ]);

        $this->assertSame('connected', $status);
        $this->assertSame('token-value', $this->options['itkt_connection']['api_token']);
        $this->assertSame('key-value', $this->options['itkt_connection']['api_key']);
    }

    public function test_save_connection_reports_rejected_token(): void
    {
        Monkey\Functions\expect('wp_remote_request')->once()->andReturn(['body' => '{}']);
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn(401);

        $status = AdminPage::saveConnectionFromPost([
            'itkt_api_token' => 'token-value',
            'itkt_api_key' => 'key-value',
        ]);

        $this->assertSame('invalid_token', $status);
        $this->assertArrayNotHasKey('itkt_connection', $this->options);
    }

    public function test_save_connection_reports_network_error(): void
    {
        Monkey\Functions\expect('wp_remote_request')->once()->andReturn(new WPErrorStub);
        Monkey\Functions\when('is_wp_error')->justReturn(true);

        $status = AdminPage::saveConnectionFromPost([
            'itkt_api_token' => 'token-value',
            'itkt_api_key' => 'key-value',
        ]);

        $this->assertSame('network_error', $status);
        $this->assertArrayNotHasKey('itkt_connection', $this->options);
    }

    public function test_save_oauth_config_requires_client_id(): void
    {
        $this->assertSame('oauth_config_invalid', AdminPage::saveOAuthConfigFromPost([]));
    }

    public function test_save_oauth_config_stores_client_id(): void
    {
        $status = AdminPage::saveOAuthConfigFromPost(['itkt_oauth_client_id' => 'client-id-value']);

        $this->assertSame('oauth_config_saved', $status);
        $this->assertSame('client-id-value', $this->options['itkt_oauth_client_id']);
        $this->assertSame(OAuthConfig::DEFAULT_SCOPES, $this->options['itkt_oauth_client_scopes']);
    }

    public function test_save_oauth_config_keeps_stored_secrets_when_fields_empty(): void
    {
        $this->options['itkt_oauth_client_id'] = 'old-client-id';
        $this->options['itkt_oauth_client_secret'] = 'old-secret';
        $this->options['itkt_oauth_client_api_key'] = 'old-key';

        $status = AdminPage::saveOAuthConfigFromPost(['itkt_oauth_client_id' => 'new-client-id']);

        $this->assertSame('oauth_config_saved', $status);
        $this->assertSame('new-client-id', $this->options['itkt_oauth_client_id']);
        $this->assertSame('old-secret', $this->options['itkt_oauth_client_secret']);
        $this->assertSame('old-key', $this->options['itkt_oauth_client_api_key']);
    }

    public function test_render_outputs_oauth_configuration_when_not_connected(): void
    {
        $output = $this->renderPage();

        $this->assertStringContainsString('name="itkt_oauth_client_id"', $output);
        $this->assertStringContainsString('name="action" value="itkt_oauth_save_config"', $output);
        $this->assertStringContainsString('admin-ajax.php?action=itkt_oauth_callback', $output);
        $this->assertStringNotContainsString('name="action" value="itkt_oauth_connect"', $output);
    }

    public function test_render_outputs_oauth_connect_button_when_configured(): void
    {
        $this->options['itkt_oauth_client_id'] = 'client-id-value';

        $output = $this->renderPage();

        $this->assertStringContainsString('name="action" value="itkt_oauth_connect"', $output);
    }

    public function test_render_outputs_connect_form_when_not_connected(): void
    {
        $output = $this->renderPage();

        $this->assertStringContainsString('name="itkt_api_token"', $output);
        $this->assertStringContainsString('name="itkt_api_key"', $output);
        $this->assertStringContainsString('name="action" value="itkt_connect"', $output);
    }

    public function test_render_outputs_disconnect_when_connected(): void
    {
        $this->options['itkt_connection'] = ['api_token' => 'token-value', 'api_key' => 'key-value'];

        $output = $this->renderPage();

        $this->assertStringContainsString('name="action" value="itkt_disconnect"', $output);
        $this->assertStringNotContainsString('name="itkt_api_token"', $output);
    }

    private function stubSuccessfulVerify(): void
    {
        Monkey\Functions\expect('wp_remote_request')->zeroOrMoreTimes()->andReturn(['body' => '{}']);
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
    }

    private function renderPage(): string
    {
        ob_start();
        (new AdminPage)->render();

        return (string) ob_get_clean();
    }
}
