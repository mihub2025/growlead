<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_requires_meta_credentials(): void
    {
        $admin = $this->admin();
        config(['services.meta.client_id' => null, 'services.meta.client_secret' => null]);

        $this->actingAs($admin)
            ->get('/crm/integrations/meta/connect')
            ->assertRedirect('/crm/integrations');
    }

    public function test_connect_redirects_to_facebook(): void
    {
        $admin = $this->admin();
        $this->configureMeta();

        $response = $this->actingAs($admin)->get('/crm/integrations/meta/connect');
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('facebook.com', $location);
        $this->assertStringContainsString('client_id=app-123', $location);
        $this->assertStringContainsString('config_id=27988803757456371', $location);
        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $oauthQuery);
        $requestedScopes = array_values(array_filter(array_map('trim', explode(',', $oauthQuery['scope'] ?? ''))));
        $this->assertEqualsCanonicalizing([
            'public_profile',
            'ads_read',
            'pages_show_list',
            'pages_read_engagement',
            'leads_retrieval',
            'business_management',
        ], $requestedScopes);
        $this->assertNotContains('email', $requestedScopes);
        $this->assertNotContains('ads_management', $requestedScopes);
        $this->assertNotContains('pages_manage_ads', $requestedScopes);
        $this->assertNotEmpty(session('meta_oauth_state'));
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $admin = $this->admin();
        $this->configureMeta();

        $this->actingAs($admin)
            ->withSession(['meta_oauth_state' => 'expected'])
            ->get('/crm/integrations/meta/callback?code=abc&state=wrong')
            ->assertRedirect('/crm/integrations?oauth=1');

        $this->assertDatabaseMissing('integrations', ['provider' => 'meta', 'status' => 'connected']);
    }

    public function test_callback_stores_connected_meta_account(): void
    {
        $admin = $this->admin();
        $this->configureMeta();

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/oauth/access_token')) {
                static $tokens = 0;
                $tokens++;

                return Http::response($tokens === 1
                    ? ['access_token' => 'short-token', 'token_type' => 'bearer', 'expires_in' => 3600]
                    : ['access_token' => 'long-token', 'token_type' => 'bearer', 'expires_in' => 5184000]);
            }

            $path = parse_url($url, PHP_URL_PATH) ?? '';
            if (preg_match('#/me$#', $path)) {
                return Http::response(['id' => '99', 'name' => 'Meta User']);
            }

            return Http::response(['data' => []]);
        });

        $this->actingAs($admin)
            ->withSession(['meta_oauth_state' => 'state-1'])
            ->get('/crm/integrations/meta/callback?code=ok&state=state-1')
            ->assertRedirect('/crm/integrations?oauth=1');

        $integration = Integration::where('provider', 'meta')->first();
        $this->assertNotNull($integration);
        $this->assertSame('connected', $integration->status);
        $this->assertSame('Meta User', data_get($integration->settings, 'account_name'));
        $this->assertSame('long-token', data_get($integration->credentials, 'token'));
    }

    public function test_callback_opens_business_picker_when_multiple_businesses_exist(): void
    {
        $admin = $this->admin();
        $this->configureMeta();

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/oauth/access_token')) {
                return Http::response(['access_token' => 'long-token', 'token_type' => 'bearer', 'expires_in' => 5184000]);
            }
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            if (preg_match('#/me$#', $path)) {
                return Http::response(['id' => '99', 'name' => 'Meta User']);
            }
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['data' => [
                    ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ['id' => 'biz-2', 'name' => 'Smr Tiles'],
                ]]);
            }

            return Http::response(['data' => []]);
        });

        $this->actingAs($admin)
            ->withSession(['meta_oauth_state' => 'state-1'])
            ->get('/crm/integrations/meta/callback?code=ok&state=state-1')
            ->assertRedirect('/crm/integrations/meta/businesses?oauth=1');

        $this->assertDatabaseMissing('campaigns', ['organization_id' => $admin->organization_id]);
    }

    public function test_disconnect_clears_meta_connection(): void
    {
        $admin = $this->admin();
        Integration::create([
            'organization_id' => $admin->organization_id,
            'provider' => 'meta',
            'name' => 'Meta Ads',
            'status' => 'connected',
            'credentials' => ['token' => 'secret'],
            'settings' => ['account_name' => 'Meta User'],
        ]);

        $this->actingAs($admin)
            ->post('/crm/integrations/meta/disconnect')
            ->assertRedirect();

        $this->assertSame('disconnected', Integration::where('provider', 'meta')->value('status'));
    }

    protected function admin()
    {
        return app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Meta Org',
            'industry' => 'generic',
            'email' => 'admin@meta.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
    }

    protected function configureMeta(): void
    {
        config([
            'services.meta.client_id' => 'app-123',
            'services.meta.client_secret' => 'secret-456',
            'services.meta.graph_version' => 'v21.0',
            'services.meta.login_config_id' => '27988803757456371',
            'services.meta.scopes' => 'public_profile,ads_read,pages_show_list,pages_read_engagement,leads_retrieval,business_management',
        ]);
    }
}
