<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Lead;
use App\Models\MetaCapiEvent;
use App\Models\Organization;
use App\Support\MetaCapiResponseSanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MetaConversionsApiService
{
    public function __construct(
        protected MetaCapiUserDataNormalizer $normalizer
    ) {}

    public function graphVersion(): string
    {
        return trim((string) config('services.meta.graph_version', config('meta.graph_version', 'v21.0')), '/');
    }

    public function metaIntegrationForOrganization(Organization|int $organization): ?Integration
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;

        return Integration::forOrganization($orgId)->where('provider', 'meta')->first();
    }

    public function isEnabled(?Integration $integration): bool
    {
        return (bool) data_get($integration?->settings, 'capi.enabled', false);
    }

    public function datasetId(?Integration $integration): ?string
    {
        $id = trim((string) data_get($integration?->settings, 'capi.dataset_id', ''));

        return $id !== '' ? $id : null;
    }

    public function accessToken(?Integration $integration): ?string
    {
        $token = (string) data_get($integration?->credentials, 'capi_access_token', '');

        return $token !== '' ? $token : null;
    }

    public function leadEventSource(?Integration $integration): string
    {
        $name = trim((string) data_get(
            $integration?->settings,
            'capi.lead_event_source',
            config('meta.capi.default_lead_event_source', 'GrowLead')
        ));

        return $name !== '' ? $name : 'GrowLead';
    }

    public function hasAccessTokenConfigured(?Integration $integration): bool
    {
        return filled($this->accessToken($integration));
    }

    public function eventNameForStatus(string $status): ?string
    {
        $map = config('meta.capi.status_event_map', []);
        $slug = $this->normalizeStatusSlug($status);

        return $map[$slug] ?? null;
    }

    public function shouldAutoSendStatus(string $status): bool
    {
        $slug = $this->normalizeStatusSlug($status);
        $allowed = array_map([$this, 'normalizeStatusSlug'], config('meta.capi.auto_send_statuses', ['qualified']));

        return in_array($slug, $allowed, true) && $this->eventNameForStatus($slug) !== null;
    }

    public function normalizeStatusSlug(string $status): string
    {
        $status = strtolower(trim($status));
        foreach (config('crm.default_lead_statuses', []) as $row) {
            $slug = strtolower((string) ($row['slug'] ?? ''));
            if ($slug !== '' && ($slug === $status || strcasecmp((string) ($row['name'] ?? ''), $status) === 0)) {
                return $slug;
            }
            foreach ($row['aliases'] ?? [] as $alias) {
                if (strcasecmp((string) $alias, $status) === 0) {
                    return $slug;
                }
            }
        }

        return str_replace([' ', '-'], '_', $status);
    }

    /**
     * Persist CAPI settings on the org Meta integration without exposing the token.
     *
     * @param  array{enabled?:bool,dataset_id?:string,lead_event_source?:string,test_event_code?:string,capi_access_token?:string|null}  $input
     */
    public function saveSettings(Integration $integration, array $input): Integration
    {
        $settings = $integration->settings ?? [];
        $capi = $settings['capi'] ?? [];

        if (array_key_exists('enabled', $input)) {
            $capi['enabled'] = (bool) $input['enabled'];
        }
        if (array_key_exists('dataset_id', $input)) {
            $capi['dataset_id'] = trim((string) $input['dataset_id']) ?: null;
        }
        if (array_key_exists('lead_event_source', $input)) {
            $source = trim((string) $input['lead_event_source']);
            $capi['lead_event_source'] = $source !== '' ? $source : config('meta.capi.default_lead_event_source', 'GrowLead');
        }
        if (array_key_exists('test_event_code', $input)) {
            $code = trim((string) $input['test_event_code']);
            $capi['test_event_code'] = $code !== '' ? $code : null;
        }

        $credentials = $integration->credentials ?? [];
        if (array_key_exists('capi_access_token', $input)) {
            $token = trim((string) ($input['capi_access_token'] ?? ''));
            if ($token !== '') {
                $credentials['capi_access_token'] = $token;
            }
            // Empty string means "leave existing token unchanged".
        }

        $settings['capi'] = $capi;
        $integration->update([
            'settings' => $settings,
            'credentials' => $credentials,
        ]);

        return $integration->fresh();
    }

    /**
     * Public settings payload safe for Blade/API (never includes raw token).
     */
    public function publicSettings(?Integration $integration): array
    {
        return [
            'enabled' => $this->isEnabled($integration),
            'dataset_id' => $this->datasetId($integration),
            'lead_event_source' => $this->leadEventSource($integration),
            'test_event_code' => data_get($integration?->settings, 'capi.test_event_code'),
            'has_access_token' => $this->hasAccessTokenConfigured($integration),
            'last_test_at' => data_get($integration?->settings, 'capi.last_test_at'),
            'last_test_ok' => data_get($integration?->settings, 'capi.last_test_ok'),
            'last_error' => data_get($integration?->settings, 'capi.last_error'),
        ];
    }

    public function buildCrmEventPayload(
        Lead $lead,
        string $metaEventName,
        int $eventTime,
        string $eventId,
        Integration $integration,
        ?string $testEventCode = null
    ): array {
        $userData = [];
        $metaLeadId = $lead->metaLeadId();
        if ($metaLeadId) {
            // Meta Instant Form lead_id must be sent raw (not hashed).
            $userData['lead_id'] = is_numeric($metaLeadId) ? (int) $metaLeadId : $metaLeadId;
        }

        $emailHash = $this->normalizer->hashEmail($lead->email);
        if ($emailHash) {
            $userData['em'] = [$emailHash];
        }

        $countryCode = $lead->organization?->phone_country
            ?? $lead->organization?->country
            ?? null;
        $phoneHash = $this->normalizer->hashPhone($lead->phone ?: $lead->whatsapp, $this->guessCountryDialCode($countryCode));
        if ($phoneHash) {
            $userData['ph'] = [$phoneHash];
        }

        if ($userData === []) {
            throw new RuntimeException('No matching user_data available for Meta CAPI (need lead_id, email, or phone).');
        }

        $event = [
            'event_name' => $metaEventName,
            'event_time' => $eventTime,
            'event_id' => $eventId,
            'action_source' => 'system_generated',
            'user_data' => $userData,
            'custom_data' => [
                'event_source' => 'crm',
                'lead_event_source' => $this->leadEventSource($integration),
            ],
        ];

        $body = ['data' => [$event]];
        if ($testEventCode) {
            $body['test_event_code'] = $testEventCode;
        }

        return $body;
    }

    /**
     * @return array{ok:bool,status:int|null,body:array,events_received:int|null,fbtrace_id:?string,messages:array,error:?string}
     */
    public function sendEvents(Integration $integration, array $payload): array
    {
        $datasetId = $this->datasetId($integration);
        $token = $this->accessToken($integration);

        if (! $datasetId) {
            throw new RuntimeException('Meta Dataset ID / Pixel ID is not configured.');
        }
        if (! $token) {
            throw new RuntimeException('Meta CAPI access token is not configured.');
        }

        $url = 'https://graph.facebook.com/'.$this->graphVersion().'/'.$datasetId.'/events';
        $timeout = (int) config('meta.capi.timeout', 15);

        try {
            $response = Http::timeout($timeout)
                ->asJson()
                ->post($url, array_merge($payload, ['access_token' => $token]));
        } catch (ConnectionException $e) {
            throw new RuntimeException('Network error contacting Meta Conversions API.', 0, $e);
        }

        $body = $response->json() ?? [];
        $sanitized = MetaCapiResponseSanitizer::sanitize($body, $token);
        $fbtrace = data_get($body, 'fbtrace_id') ?? data_get($body, 'error.fbtrace_id');
        $messages = [];
        if (isset($body['messages']) && is_array($body['messages'])) {
            $messages = $body['messages'];
        }

        if ($response->failed() || isset($body['error'])) {
            $error = MetaCapiResponseSanitizer::message($body, $token);

            return [
                'ok' => false,
                'status' => $response->status(),
                'body' => is_array($sanitized) ? $sanitized : ['raw' => $sanitized],
                'events_received' => null,
                'fbtrace_id' => $fbtrace ? (string) $fbtrace : null,
                'messages' => $messages,
                'error' => $error,
                'retryable' => $this->isRetryableHttpStatus($response->status(), $body),
            ];
        }

        return [
            'ok' => true,
            'status' => $response->status(),
            'body' => is_array($sanitized) ? $sanitized : ['raw' => $sanitized],
            'events_received' => isset($body['events_received']) ? (int) $body['events_received'] : null,
            'fbtrace_id' => $fbtrace ? (string) $fbtrace : null,
            'messages' => $messages,
            'error' => null,
            'retryable' => false,
        ];
    }

    public function sendQueuedEvent(MetaCapiEvent $event): MetaCapiEvent
    {
        $event = $event->fresh(['lead.organization', 'integration']) ?? $event;
        if ($event->status === MetaCapiEvent::STATUS_SENT) {
            return $event;
        }

        $integration = $event->integration
            ?? $this->metaIntegrationForOrganization((int) $event->organization_id);

        if (! $integration || (int) $integration->organization_id !== (int) $event->organization_id) {
            return $this->markFailed($event, 'Organization Meta connection missing or mismatched.', null, null, false);
        }

        if (! $this->isEnabled($integration) && ! $event->is_test) {
            return $this->markSkipped($event, 'Conversions API is disabled for this organization.');
        }

        $lead = $event->lead;
        if (! $lead || (int) $lead->organization_id !== (int) $event->organization_id) {
            return $this->markFailed($event, 'Lead missing or belongs to another organization.', null, null, false);
        }

        try {
            $payload = $this->buildCrmEventPayload(
                $lead,
                $event->meta_event_name,
                (int) $event->event_time,
                $event->event_id,
                $integration,
                $event->is_test ? data_get($integration->settings, 'capi.test_event_code') : null
            );
        } catch (Throwable $e) {
            return $this->markFailed($event, MetaCapiResponseSanitizer::message($e->getMessage()), null, null, false);
        }

        $event->increment('attempts');
        $event->refresh();

        try {
            $result = $this->sendEvents($integration, $payload);
        } catch (Throwable $e) {
            Log::warning('meta_capi.send_failed', [
                'organization_id' => $event->organization_id,
                'meta_capi_event_id' => $event->id,
                'error' => MetaCapiResponseSanitizer::message($e->getMessage()),
            ]);

            $this->markFailed($event, MetaCapiResponseSanitizer::message($e->getMessage()), null, null, true);
            throw $e;
        }

        if ($result['ok']) {
            return $this->markSent($event, $result);
        }

        $retryable = (bool) ($result['retryable'] ?? false);
        $this->markFailed(
            $event,
            $result['error'] ?? 'Meta Conversions API rejected the event.',
            $result['status'] ?? null,
            $result['body'] ?? null,
            $retryable
        );

        if ($retryable) {
            throw new RuntimeException($result['error'] ?? 'Temporary Meta CAPI failure.');
        }

        return $event->fresh();
    }

    /**
     * Send an admin-initiated test event (uses configured test_event_code).
     *
     * @return array{ok:bool,message:string,result:array}
     */
    public function sendTestEvent(Integration $integration, ?Lead $sampleLead = null, ?string $testEventCode = null): array
    {
        $code = trim((string) ($testEventCode ?: data_get($integration->settings, 'capi.test_event_code', '')));
        if ($code === '') {
            return ['ok' => false, 'message' => 'Enter a Meta Test Event Code.', 'result' => []];
        }

        $lead = $sampleLead;
        if (! $lead) {
            $lead = Lead::forOrganization($integration->organization_id)
                ->where(function ($q) {
                    $q->whereNotNull('external_id')->where('external_id', '!=', '');
                })
                ->latest('id')
                ->first();
        }

        if (! $lead) {
            return ['ok' => false, 'message' => 'No Meta lead found in this organization to use for a test event.', 'result' => []];
        }

        $eventId = 'campaignpilot:test:'.$integration->organization_id.':'.$lead->id.':'.time();
        $eventTime = now()->timestamp;

        try {
            $payload = $this->buildCrmEventPayload(
                $lead,
                $this->eventNameForStatus('qualified') ?: 'Qualified',
                $eventTime,
                $eventId,
                $integration,
                $code
            );
            $result = $this->sendEvents($integration, $payload);
        } catch (Throwable $e) {
            $message = MetaCapiResponseSanitizer::message($e->getMessage());
            $this->recordTestOutcome($integration, false, $message);

            return ['ok' => false, 'message' => $message, 'result' => []];
        }

        MetaCapiEvent::create([
            'organization_id' => $integration->organization_id,
            'integration_id' => $integration->id,
            'lead_id' => $lead->id,
            'meta_lead_id' => $lead->metaLeadId(),
            'from_status' => null,
            'crm_status' => 'qualified',
            'meta_event_name' => $this->eventNameForStatus('qualified') ?: 'Qualified',
            'event_id' => $eventId,
            'event_time' => $eventTime,
            'status' => $result['ok'] ? MetaCapiEvent::STATUS_SENT : MetaCapiEvent::STATUS_FAILED,
            'attempts' => 1,
            'response_code' => $result['status'] ?? null,
            'response_body_sanitized' => $result['body'] ?? null,
            'error_message' => $result['error'] ?? null,
            'is_test' => true,
            'sent_at' => $result['ok'] ? now() : null,
        ]);

        if ($result['ok']) {
            $parts = ['Meta received the test event successfully.'];
            if ($result['events_received'] !== null) {
                $parts[] = 'events_received='.$result['events_received'];
            }
            if ($result['fbtrace_id']) {
                $parts[] = 'fbtrace_id='.$result['fbtrace_id'];
            }
            foreach ($result['messages'] as $msg) {
                if (is_string($msg) && $msg !== '') {
                    $parts[] = $msg;
                }
            }
            $message = implode(' ', $parts);
            $this->recordTestOutcome($integration, true, null);

            return ['ok' => true, 'message' => $message, 'result' => $result];
        }

        $message = $result['error'] ?? 'Test event failed.';
        $this->recordTestOutcome($integration, false, $message);

        return ['ok' => false, 'message' => $message, 'result' => $result];
    }

    public function makeEventId(int $organizationId, int $leadId, string $crmStatus, int $transitionId): string
    {
        $status = $this->normalizeStatusSlug($crmStatus);

        return 'campaignpilot:'.$organizationId.':'.$leadId.':'.$status.':'.$transitionId;
    }

    protected function markSent(MetaCapiEvent $event, array $result): MetaCapiEvent
    {
        $event->update([
            'status' => MetaCapiEvent::STATUS_SENT,
            'response_code' => $result['status'] ?? null,
            'response_body_sanitized' => $result['body'] ?? null,
            'error_message' => null,
            'sent_at' => now(),
        ]);

        return $event->fresh();
    }

    protected function markFailed(
        MetaCapiEvent $event,
        string $message,
        ?int $code,
        mixed $body,
        bool $retryable
    ): MetaCapiEvent {
        $event->update([
            'status' => MetaCapiEvent::STATUS_FAILED,
            'response_code' => $code,
            'response_body_sanitized' => is_array($body) ? $body : ($body ? ['message' => (string) $body] : $event->response_body_sanitized),
            'error_message' => MetaCapiResponseSanitizer::message($message),
        ]);

        // Store retryable hint without schema change.
        if ($retryable) {
            // Job layer decides whether to rethrow.
        }

        return $event->fresh();
    }

    protected function markSkipped(MetaCapiEvent $event, string $reason): MetaCapiEvent
    {
        $event->update([
            'status' => MetaCapiEvent::STATUS_SKIPPED,
            'error_message' => $reason,
        ]);

        return $event->fresh();
    }

    protected function recordTestOutcome(Integration $integration, bool $ok, ?string $error): void
    {
        $settings = $integration->settings ?? [];
        $capi = $settings['capi'] ?? [];
        $capi['last_test_at'] = now()->toIso8601String();
        $capi['last_test_ok'] = $ok;
        $capi['last_error'] = $ok ? null : $error;
        $settings['capi'] = $capi;
        $integration->update(['settings' => $settings]);
    }

    protected function isRetryableHttpStatus(int $status, array $body): bool
    {
        if (in_array($status, [429, 500, 502, 503, 504], true)) {
            return true;
        }

        $code = (int) data_get($body, 'error.code', 0);
        $subcode = (int) data_get($body, 'error.error_subcode', 0);
        // Meta rate-limit / temporary codes commonly seen.
        if (in_array($code, [1, 2, 4, 17, 32, 613], true)) {
            return true;
        }
        if ($subcode === 2446079) {
            return true;
        }

        return false;
    }

    protected function guessCountryDialCode(?string $country): ?string
    {
        if (! $country) {
            return null;
        }
        $country = strtoupper(trim($country));
        $map = [
            'PK' => '92', 'PAK' => '92', 'PAKISTAN' => '92',
            'AE' => '971', 'UAE' => '971', 'ARE' => '971',
            'US' => '1', 'USA' => '1', 'CA' => '1', 'CAN' => '1',
            'GB' => '44', 'UK' => '44', 'IN' => '91', 'IND' => '91',
            'SA' => '966', 'SAU' => '966',
        ];

        if (isset($map[$country])) {
            return $map[$country];
        }
        if (preg_match('/^\d{1,4}$/', $country)) {
            return $country;
        }

        return null;
    }
}
