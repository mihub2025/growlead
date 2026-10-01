<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Integration;
use App\Models\Lead;
use App\Services\Integrations\MetaSyncService;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_imports_facebook_campaigns_and_leads(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id);
        $this->fakeGraph();

        $summary = app(MetaSyncService::class)->sync($integration->fresh());

        $this->assertSame(1, $summary['campaigns_imported']);
        $this->assertSame(1, $summary['leads_imported']);

        $campaign = Campaign::where('external_campaign_id', '222')->first();
        $this->assertNotNull($campaign);
        $this->assertSame('Spring Leads', $campaign->name);
        $this->assertSame('active', $campaign->status);
        $this->assertSame('synced', $campaign->sync_status);
        $this->assertEquals(500, (float) $campaign->daily_budget);
        $this->assertEquals(12.5, (float) $campaign->total_spend);

        $lead = Lead::where('external_id', 'lead-9')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Ali', $lead->first_name);
        $this->assertSame('Khan', $lead->last_name);
        $this->assertSame('ali@example.com', $lead->email);
        $this->assertSame($campaign->id, $lead->campaign_id);
        $this->assertSame('meta', $lead->source->slug);
        $this->assertSame('personal', $campaign->meta_business_id);
        $this->assertSame('Ali Khan', data_get($lead->meta_payload, 'field_data.0.values.0'));
        $this->assertSame('Green Aura', data_get($lead->meta_payload, 'page_name'));
        $this->assertSame('Quote form', data_get($lead->meta_payload, 'form_name'));
    }

    public function test_lead_detail_shows_meta_form_submission_rows(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id);
        $this->fakeGraph();
        app(MetaSyncService::class)->sync($integration->fresh());

        $lead = Lead::where('external_id', 'lead-9')->first();

        $this->actingAs($admin)
            ->get('/crm/leads/'.$lead->id)
            ->assertOk()
            ->assertSee('Lead Detail')
            ->assertSee('Meta Lead Form Submission Details')
            ->assertSee('Ali Khan')
            ->assertSee('full_name')
            ->assertSee('Green Aura')
            ->assertSee('Quote form')
            ->assertSee('Values are captured from the Meta Lead Ads form submission.');
    }

    public function test_sync_does_not_duplicate_campaigns_or_leads(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id);
        $this->fakeGraph();

        app(MetaSyncService::class)->sync($integration->fresh());
        app(MetaSyncService::class)->sync($integration->fresh());

        $this->assertSame(1, Campaign::where('external_campaign_id', '222')->count());
        $this->assertSame(1, Lead::where('external_id', 'lead-9')->count());
    }

    public function test_sync_now_imports_from_integrations_page(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $this->connectedMeta($admin->organization_id);
        $this->fakeGraph();

        $this->actingAs($admin)
            ->from('/crm/integrations')
            ->post('/crm/integrations/meta/sync')
            ->assertRedirect('/crm/integrations');

        $this->assertDatabaseHas('campaigns', [
            'organization_id' => $admin->organization_id,
            'external_campaign_id' => '222',
            'name' => 'Spring Leads',
        ]);
    }

    public function test_sync_imports_only_selected_businesses(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id, [
            ['id' => 'biz-1', 'name' => 'Green Aura'],
        ]);
        $this->fakeTwoBusinesses();

        $summary = app(MetaSyncService::class)->sync($integration->fresh());

        $this->assertSame(1, $summary['campaigns_imported']);
        $this->assertDatabaseHas('campaigns', [
            'external_campaign_id' => '222',
            'meta_business_id' => 'biz-1',
            'meta_business_name' => 'Green Aura',
        ]);
        $this->assertDatabaseMissing('campaigns', ['external_campaign_id' => '333']);
    }

    public function test_sync_groups_ad_accounts_when_business_edges_are_empty(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id, [
            ['id' => 'biz-1', 'name' => 'Green Aura'],
        ]);

        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';
            if (str_contains($path, 'debug_token')) {
                return Http::response(['data' => [
                    'scopes' => ['ads_read', 'ads_management', 'business_management'],
                ]]);
            }
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['data' => [
                    ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
                ]]);
            }
            if (str_contains($path, '/me/adaccounts')) {
                return Http::response(['data' => [
                    [
                        'id' => 'act_111',
                        'name' => 'Green Aura Ads',
                        'currency' => 'PKR',
                        'business' => ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ],
                    [
                        'id' => 'act_222',
                        'name' => 'SMR Ads',
                        'currency' => 'PKR',
                        'business' => ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
                    ],
                ]]);
            }
            if (str_contains($path, '/act_111/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '222',
                    'name' => 'Spring Leads',
                    'status' => 'ACTIVE',
                    'effective_status' => 'ACTIVE',
                    'objective' => 'OUTCOME_LEADS',
                    'daily_budget' => '50000',
                ]]]);
            }
            if (str_contains($path, '/act_222/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '333',
                    'name' => 'Tiles Push',
                    'status' => 'ACTIVE',
                    'effective_status' => 'ACTIVE',
                    'daily_budget' => '20000',
                ]]]);
            }
            if (str_contains($path, '/act_111/insights')) {
                return Http::response(['data' => [['campaign_id' => '222', 'spend' => '12.50']]]);
            }

            return Http::response(['data' => []]);
        });

        $summary = app(MetaSyncService::class)->sync($integration->fresh());

        $this->assertSame(1, $summary['campaigns_imported']);
        $this->assertDatabaseHas('campaigns', [
            'external_campaign_id' => '222',
            'meta_business_id' => 'biz-1',
        ]);
        $this->assertDatabaseMissing('campaigns', ['external_campaign_id' => '333']);
    }

    public function test_sync_now_redirects_to_business_picker_when_needed(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $this->connectedMeta($admin->organization_id);
        $this->fakeTwoBusinesses();

        $this->actingAs($admin)
            ->from('/crm/integrations')
            ->post('/crm/integrations/meta/sync')
            ->assertRedirect('/crm/integrations/meta/businesses');
    }

    public function test_saving_businesses_syncs_selected_accounts(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $this->connectedMeta($admin->organization_id);
        $this->fakeTwoBusinesses();

        $this->actingAs($admin)
            ->post('/crm/integrations/meta/businesses', ['business_ids' => ['biz-2']])
            ->assertRedirect('/crm/integrations');

        $this->assertDatabaseHas('campaigns', [
            'external_campaign_id' => '333',
            'meta_business_name' => 'Smr Tiles',
        ]);
        $this->assertDatabaseMissing('campaigns', ['external_campaign_id' => '222']);
    }

    public function test_list_businesses_keeps_cached_managers_when_graph_is_rate_limited(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id, [
            ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
        ]);
        $integration->update([
            'settings' => array_merge($integration->fresh()->settings ?? [], [
                'business_catalog' => [
                    ['id' => 'biz-1', 'name' => 'Green Aura', 'ad_accounts' => [['id' => 'act_111', 'name' => 'Green Aura Ads']]],
                    ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting', 'ad_accounts' => [['id' => 'act_222', 'name' => 'SMR Ads']]],
                    ['id' => 'personal', 'name' => 'Personal ad accounts', 'ad_accounts' => [['id' => 'act_p', 'name' => 'Personal']]],
                ],
            ]),
        ]);

        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['error' => ['message' => '(#4) Application request limit reached', 'code' => 4]], 400);
            }
            if (str_contains($path, '/me/adaccounts')) {
                return Http::response(['data' => [[
                    'id' => 'act_222',
                    'name' => 'SMR Ads',
                    'business' => ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
                ]]]);
            }

            return Http::response(['data' => []]);
        });

        $warnings = [];
        $catalog = app(MetaSyncService::class)->listBusinesses($integration->fresh(), $warnings);
        $ids = collect($catalog)->pluck('id')->all();

        $this->assertContains('biz-1', $ids);
        $this->assertContains('biz-2', $ids);
        $this->assertContains('personal', $ids);
        $this->assertTrue(collect($warnings)->contains(fn ($message) => str_contains(strtolower((string) $message), 'rate')));
    }

    public function test_list_businesses_includes_managers_returned_by_facebook(): void
    {
        $admin = $this->admin();
        $this->configureMeta();
        $integration = $this->connectedMeta($admin->organization_id);
        $integration->update([
            'settings' => array_merge($integration->fresh()->settings ?? [], [
                'business_catalog' => [
                    ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting', 'ad_accounts' => []],
                ],
            ]),
        ]);

        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['data' => [
                    ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
                    ['id' => 'biz-3', 'name' => 'RevX'],
                    ['id' => 'biz-4', 'name' => 'Joeyco'],
                ]]);
            }
            if (str_contains($path, '/me/adaccounts')) {
                return Http::response(['data' => [
                    [
                        'id' => 'act_222',
                        'name' => 'SMR Ads',
                        'business' => ['id' => 'biz-2', 'name' => 'SMR Trading & Contracting'],
                    ],
                ]]);
            }

            return Http::response(['data' => []]);
        });

        $warnings = [];
        $catalog = app(MetaSyncService::class)->listBusinesses($integration->fresh(), $warnings);
        $names = collect($catalog)->pluck('name')->all();

        $this->assertContains('Green Aura', $names);
        $this->assertContains('RevX', $names);
        $this->assertContains('Joeyco', $names);
        $this->assertContains('SMR Trading & Contracting', $names);
    }

    public function test_campaign_center_filters_by_business(): void
    {
        $admin = $this->admin();
        Campaign::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Aura Ads',
            'meta_business_id' => 'biz-1',
            'meta_business_name' => 'Green Aura',
        ]);
        Campaign::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Tiles Ads',
            'meta_business_id' => 'biz-2',
            'meta_business_name' => 'Smr Tiles',
        ]);

        $this->actingAs($admin)
            ->get('/crm/campaigns?business_id=biz-1')
            ->assertOk()
            ->assertSee('Aura Ads')
            ->assertDontSee('Tiles Ads')
            ->assertSee('Green Aura');
    }

    protected function admin()
    {
        return app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Meta Sync Org',
            'industry' => 'generic',
            'email' => 'admin@metasync.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
    }

    protected function connectedMeta(int $organizationId, array $selected = []): Integration
    {
        return Integration::create([
            'organization_id' => $organizationId,
            'provider' => 'meta',
            'name' => 'Meta Ads',
            'status' => 'connected',
            'credentials' => ['token' => 'user-token'],
            'settings' => [
                'account_name' => 'Smr Tiles Golimar',
                'selected_businesses' => $selected,
            ],
        ]);
    }

    protected function configureMeta(): void
    {
        config([
            'services.meta.client_id' => 'app-123',
            'services.meta.client_secret' => 'secret-456',
            'services.meta.graph_version' => 'v21.0',
        ]);
    }

    protected function fakeGraph(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if (str_contains($path, 'debug_token')) {
                return Http::response(['data' => [
                    'scopes' => ['ads_read', 'ads_management', 'pages_show_list', 'leads_retrieval'],
                ]]);
            }
            if (str_contains($path, '/me/adaccounts')) {
                return Http::response(['data' => [[
                    'id' => 'act_111',
                    'account_id' => '111',
                    'name' => 'Green Aura Ads',
                    'currency' => 'PKR',
                ]]]);
            }
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['data' => []]);
            }
            if (str_contains($path, '/act_111/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '222',
                    'name' => 'Spring Leads',
                    'status' => 'ACTIVE',
                    'effective_status' => 'ACTIVE',
                    'objective' => 'OUTCOME_LEADS',
                    'daily_budget' => '50000',
                    'start_time' => '2026-01-01T00:00:00+0000',
                ]]]);
            }
            if (str_contains($path, '/act_111/insights')) {
                return Http::response(['data' => [['campaign_id' => '222', 'spend' => '12.50']]]);
            }
            if (str_contains($path, '/me/accounts')) {
                return Http::response(['data' => [[
                    'id' => 'page-1',
                    'name' => 'Green Aura',
                    'access_token' => 'page-token',
                ]]]);
            }
            if (str_contains($path, '/page-1/leadgen_forms')) {
                return Http::response(['data' => [['id' => 'form-1', 'name' => 'Quote form', 'status' => 'ACTIVE']]]);
            }
            if (str_contains($path, '/form-1/leads')) {
                return Http::response(['data' => [[
                    'id' => 'lead-9',
                    'created_time' => '2026-08-01T10:00:00+0000',
                    'campaign_id' => '222',
                    'field_data' => [
                        ['name' => 'full_name', 'values' => ['Ali Khan']],
                        ['name' => 'email', 'values' => ['ali@example.com']],
                        ['name' => 'phone_number', 'values' => ['03001234567']],
                    ],
                ]]]);
            }

            return Http::response(['data' => []]);
        });
    }

    protected function fakeTwoBusinesses(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if (str_contains($path, 'debug_token')) {
                return Http::response(['data' => [
                    'scopes' => ['ads_read', 'ads_management', 'pages_show_list', 'leads_retrieval', 'business_management'],
                ]]);
            }
            if (str_contains($path, '/me/businesses')) {
                return Http::response(['data' => [
                    ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ['id' => 'biz-2', 'name' => 'Smr Tiles'],
                ]]);
            }
            if (str_contains($path, '/me/adaccounts')) {
                return Http::response(['data' => [
                    [
                        'id' => 'act_111',
                        'account_id' => '111',
                        'name' => 'Green Aura Ads',
                        'currency' => 'PKR',
                        'business' => ['id' => 'biz-1', 'name' => 'Green Aura'],
                    ],
                    [
                        'id' => 'act_222',
                        'account_id' => '222',
                        'name' => 'Tiles Ads',
                        'currency' => 'PKR',
                        'business' => ['id' => 'biz-2', 'name' => 'Smr Tiles'],
                    ],
                ]]);
            }
            if (str_contains($path, '/act_111/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '222',
                    'name' => 'Spring Leads',
                    'status' => 'ACTIVE',
                    'effective_status' => 'ACTIVE',
                    'objective' => 'OUTCOME_LEADS',
                    'daily_budget' => '50000',
                ]]]);
            }
            if (str_contains($path, '/act_222/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '333',
                    'name' => 'Tiles Push',
                    'status' => 'ACTIVE',
                    'effective_status' => 'ACTIVE',
                    'objective' => 'OUTCOME_LEADS',
                    'daily_budget' => '20000',
                ]]]);
            }
            if (str_contains($path, '/act_111/insights')) {
                return Http::response(['data' => [['campaign_id' => '222', 'spend' => '12.50']]]);
            }
            if (str_contains($path, '/act_222/insights')) {
                return Http::response(['data' => [['campaign_id' => '333', 'spend' => '8.00']]]);
            }

            return Http::response(['data' => []]);
        });
    }
}
