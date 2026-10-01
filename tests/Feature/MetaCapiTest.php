<?php

namespace Tests\Feature;

use App\Jobs\SendMetaCapiLeadEventJob;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MetaCapiEvent;
use App\Models\User;
use App\Services\Integrations\MetaCapiUserDataNormalizer;
use App\Services\Integrations\MetaConversionsApiService;
use App\Services\LeadService;
use App\Services\OrganizationProvisioner;
use App\Support\MetaCapiResponseSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaCapiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_to_qualified_queues_capi_event(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();

        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);

        $event = MetaCapiEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('qualified', $event->crm_status);
        $this->assertSame('Qualified', $event->meta_event_name);
        $this->assertSame('pending', $event->status);
        $this->assertSame((string) $lead->external_id, $event->meta_lead_id);
        Queue::assertPushed(SendMetaCapiLeadEventJob::class, fn ($job) => $job->metaCapiEventId === $event->id);
    }

    public function test_qualified_to_qualified_does_not_duplicate(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi(['status' => 'qualified']);

        app(LeadService::class)->update($lead, ['status' => 'qualified', 'notes' => 'same'], $admin);

        $this->assertSame(0, MetaCapiEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_unrelated_status_change_does_not_send(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();

        app(LeadService::class)->update($lead, ['status' => 'working_deal'], $admin);

        $this->assertSame(0, MetaCapiEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_capi_disabled_does_not_send(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi(capiEnabled: false);

        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);

        $this->assertSame(0, MetaCapiEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_uses_correct_organization_configuration(): void
    {
        Queue::fake();
        [$adminA, $leadA] = $this->seedMetaLeadWithCapi(
            capi: ['dataset_id' => 'pixel-org-a', 'token' => 'token-org-a']
        );

        app(LeadService::class)->update($leadA, ['status' => 'qualified'], $adminA);
        $event = MetaCapiEvent::first();
        $this->assertSame($adminA->organization_id, $event->organization_id);

        Http::fake([
            'https://graph.facebook.com/v21.0/pixel-org-a/events' => Http::response(['events_received' => 1], 200),
        ]);

        (new SendMetaCapiLeadEventJob($event->id))->handle(app(MetaConversionsApiService::class));

        Http::assertSent(function ($request) use ($leadA) {
            $url = $request->url();
            $body = $request->data();

            return str_contains($url, 'pixel-org-a/events')
                && ($body['access_token'] ?? null) === 'token-org-a'
                && (int) data_get($body, 'data.0.user_data.lead_id') === (int) $leadA->external_id
                && data_get($body, 'data.0.custom_data.event_source') === 'crm'
                && ! str_contains(json_encode($body), 'token-org-b');
        });

        $this->assertSame('sent', $event->fresh()->status);
        $this->assertSame($adminA->organization_id, $event->fresh()->organization_id);
    }

    public function test_organization_b_cannot_use_organization_a_credentials(): void
    {
        [$adminA] = $this->seedMetaLeadWithCapi(
            email: 'a@org.test',
            orgName: 'Org A',
            capi: ['dataset_id' => 'pixel-a', 'token' => 'secret-a']
        );
        [$adminB, $leadB] = $this->seedMetaLeadWithCapi(
            email: 'b@org.test',
            orgName: 'Org B',
            externalId: '999888777666555',
            capi: ['dataset_id' => 'pixel-b', 'token' => 'secret-b']
        );

        Queue::fake();
        app(LeadService::class)->update($leadB, ['status' => 'qualified'], $adminB);
        $event = MetaCapiEvent::where('organization_id', $adminB->organization_id)->first();

        // Tamper: point event at Org A integration — service must reject mismatch.
        $integrationA = Integration::forOrganization($adminA->organization_id)->where('provider', 'meta')->first();
        $event->update(['integration_id' => $integrationA->id]);

        Http::fake();
        $result = app(MetaConversionsApiService::class)->sendQueuedEvent($event->fresh());

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('mismatched', strtolower((string) $result->error_message));
        Http::assertNothingSent();
    }

    public function test_meta_lead_id_included_in_payload(): void
    {
        [, $lead] = $this->seedMetaLeadWithCapi(externalId: '1234567890123456');
        $integration = Integration::forOrganization($lead->organization_id)->where('provider', 'meta')->first();
        $service = app(MetaConversionsApiService::class);

        $payload = $service->buildCrmEventPayload(
            $lead,
            'Qualified',
            now()->timestamp,
            'campaignpilot:1:1:qualified:1',
            $integration
        );

        $this->assertSame(1234567890123456, $payload['data'][0]['user_data']['lead_id']);
        $this->assertSame('system_generated', $payload['data'][0]['action_source']);
        $this->assertSame('crm', $payload['data'][0]['custom_data']['event_source']);
        $this->assertArrayNotHasKey('test_event_code', $payload);
    }

    public function test_email_normalization_and_hashing(): void
    {
        $n = app(MetaCapiUserDataNormalizer::class);
        $this->assertSame(
            hash('sha256', 'peter.muller@example.com'),
            $n->hashEmail('  Peter.Muller@Example.com ')
        );
        $this->assertNull($n->hashEmail('not-an-email'));
        $this->assertSame(hash('sha256', 'a@b.co'), $n->sha256(hash('sha256', 'a@b.co'))); // no double-hash of digest
    }

    public function test_phone_normalization_and_hashing(): void
    {
        $n = app(MetaCapiUserDataNormalizer::class);
        $this->assertSame(hash('sha256', '923001234567'), $n->hashPhone('03001234567', '92'));
        $this->assertSame(hash('sha256', '15551234567'), $n->hashPhone('+1 (555) 123-4567'));
        $this->assertNull($n->hashPhone(null));
    }

    public function test_same_event_id_retained_on_retry(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();
        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);
        $event = MetaCapiEvent::first();
        $originalId = $event->event_id;

        $event->update(['status' => 'failed', 'error_message' => 'temp']);
        SendMetaCapiLeadEventJob::dispatch($event->id);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
        (new SendMetaCapiLeadEventJob($event->id))->handle(app(MetaConversionsApiService::class));

        $this->assertSame($originalId, $event->fresh()->event_id);
        $this->assertSame('sent', $event->fresh()->status);
    }

    public function test_successful_meta_response_marks_sent(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();
        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);
        $event = MetaCapiEvent::first();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'fbtrace_id' => 'TRACE123',
            ], 200),
        ]);

        (new SendMetaCapiLeadEventJob($event->id))->handle(app(MetaConversionsApiService::class));

        $event->refresh();
        $this->assertSame('sent', $event->status);
        $this->assertNotNull($event->sent_at);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(1, data_get($event->response_body_sanitized, 'events_received'));
    }

    public function test_temporary_failure_is_retryable(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();
        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);
        $event = MetaCapiEvent::first();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'rate limit', 'code' => 17]], 429),
        ]);

        try {
            (new SendMetaCapiLeadEventJob($event->id))->handle(app(MetaConversionsApiService::class));
            $this->fail('Expected retryable exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('rate limit', strtolower($e->getMessage()));
        }

        $this->assertSame('failed', $event->fresh()->status);
        $this->assertSame(1, $event->fresh()->attempts);
    }

    public function test_permanent_validation_error_recorded_without_endless_retry_signal(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();
        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);
        $event = MetaCapiEvent::first();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid parameter', 'code' => 100, 'type' => 'OAuthException'],
            ], 400),
        ]);

        // Permanent errors should not throw (job completes; no Laravel retry).
        (new SendMetaCapiLeadEventJob($event->id))->handle(app(MetaConversionsApiService::class));

        $event->refresh();
        $this->assertSame('failed', $event->status);
        $this->assertStringContainsString('Invalid parameter', (string) $event->error_message);
    }

    public function test_access_token_never_appears_in_stored_response_or_public_settings(): void
    {
        $token = 'EAABSECRETTOKENVALUE999';
        [$admin, $lead] = $this->seedMetaLeadWithCapi(capi: ['dataset_id' => 'pix', 'token' => $token]);
        $integration = Integration::forOrganization($admin->organization_id)->where('provider', 'meta')->first();

        $public = app(MetaConversionsApiService::class)->publicSettings($integration);
        $this->assertTrue($public['has_access_token']);
        $this->assertArrayNotHasKey('capi_access_token', $public);
        $this->assertStringNotContainsString($token, json_encode($public));

        $sanitized = MetaCapiResponseSanitizer::sanitize([
            'error' => ['message' => 'bad '.$token],
            'access_token' => $token,
        ], $token);
        $this->assertSame('[redacted]', $sanitized['access_token']);
        $this->assertStringNotContainsString($token, json_encode($sanitized));

        $this->actingAs($admin)
            ->get('/crm/integrations/meta/capi')
            ->assertOk()
            ->assertDontSee($token);
    }

    public function test_test_event_code_only_included_when_requested(): void
    {
        [, $lead] = $this->seedMetaLeadWithCapi();
        $integration = Integration::forOrganization($lead->organization_id)->where('provider', 'meta')->first();
        $service = app(MetaConversionsApiService::class);

        $normal = $service->buildCrmEventPayload($lead, 'Qualified', time(), 'eid-1', $integration, null);
        $this->assertArrayNotHasKey('test_event_code', $normal);

        $test = $service->buildCrmEventPayload($lead, 'Qualified', time(), 'eid-2', $integration, 'TESTCODE');
        $this->assertSame('TESTCODE', $test['test_event_code']);
    }

    public function test_settings_page_saves_without_exposing_token(): void
    {
        [$admin] = $this->seedMetaLeadWithCapi(capiEnabled: false, capi: ['dataset_id' => '', 'token' => '']);

        $this->actingAs($admin)
            ->post('/crm/integrations/meta/capi', [
                'enabled' => '1',
                'dataset_id' => '555444333',
                'lead_event_source' => 'GrowLead',
                'capi_access_token' => 'EAANEWTOKEN',
                'test_event_code' => 'TEST1',
            ])
            ->assertRedirect('/crm/integrations/meta/capi');

        $integration = Integration::forOrganization($admin->organization_id)->where('provider', 'meta')->first();
        $this->assertTrue((bool) data_get($integration->settings, 'capi.enabled'));
        $this->assertSame('555444333', data_get($integration->settings, 'capi.dataset_id'));
        $this->assertSame('EAANEWTOKEN', data_get($integration->credentials, 'capi_access_token'));

        $this->actingAs($admin)
            ->get('/crm/integrations/meta/capi')
            ->assertOk()
            ->assertDontSee('EAANEWTOKEN')
            ->assertSee('A token is saved');
    }

    public function test_re_qualify_after_other_status_creates_new_event(): void
    {
        Queue::fake();
        [$admin, $lead] = $this->seedMetaLeadWithCapi();

        app(LeadService::class)->update($lead, ['status' => 'qualified'], $admin);
        app(LeadService::class)->update($lead->fresh(), ['status' => 'working_deal'], $admin);
        app(LeadService::class)->update($lead->fresh(), ['status' => 'qualified'], $admin);

        $this->assertSame(2, MetaCapiEvent::count());
        $ids = MetaCapiEvent::pluck('event_id')->all();
        $this->assertCount(2, array_unique($ids));
    }

    /**
     * @return array{0:User,1:Lead}
     */
    protected function seedMetaLeadWithCapi(
        array $leadAttrs = [],
        bool $capiEnabled = true,
        array $capi = [],
        string $email = 'admin@capi.test',
        string $orgName = 'CAPI Org',
        string $externalId = '123456789012345'
    ): array {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => $orgName,
            'industry' => 'generic',
            'email' => $email,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        config([
            'services.meta.graph_version' => 'v21.0',
            'meta.capi.retry_attempts' => 3,
        ]);

        $dataset = $capi['dataset_id'] ?? '111222333';
        $token = array_key_exists('token', $capi) ? $capi['token'] : 'capi-token-secret';

        Integration::create([
            'organization_id' => $admin->organization_id,
            'provider' => 'meta',
            'name' => 'Meta Ads',
            'status' => 'connected',
            'credentials' => array_filter([
                'token' => 'oauth-user-token',
                'capi_access_token' => $token !== '' ? $token : null,
            ]),
            'settings' => [
                'account_name' => 'Test',
                'capi' => [
                    'enabled' => $capiEnabled,
                    'dataset_id' => $dataset !== '' ? $dataset : null,
                    'lead_event_source' => 'GrowLead',
                    'test_event_code' => null,
                ],
            ],
        ]);

        $source = LeadSource::forOrganization($admin->organization_id)->where('slug', 'meta')->first()
            ?? LeadSource::create([
                'organization_id' => $admin->organization_id,
                'name' => 'Meta',
                'slug' => 'meta',
                'type' => 'ads',
            ]);

        $lead = Lead::create(array_merge([
            'organization_id' => $admin->organization_id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
            'phone' => '03001234567',
            'source_id' => $source->id,
            'status' => 'not_contacted',
            'external_id' => $externalId,
            'utm_source' => 'facebook',
            'meta_created_at' => now()->subDay(),
            'meta_payload' => [
                'lead_id' => $externalId,
                'form_id' => 'form-1',
                'page_id' => 'page-1',
            ],
        ], $leadAttrs));

        return [$admin, $lead];
    }
}
