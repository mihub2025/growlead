<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Jobs\SendMetaCapiLeadEventJob;
use App\Models\Integration;
use App\Models\MetaCapiEvent;
use App\Services\Integrations\MetaConversionsApiService;
use Illuminate\Http\Request;

class MetaCapiController extends Controller
{
    public function edit(Request $request, MetaConversionsApiService $capi)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->metaIntegration($request);
        if (! $integration) {
            return redirect()->route('crm.integrations.index')
                ->with('error', 'Connect Meta Ads before configuring Conversions API.');
        }

        $settings = $capi->publicSettings($integration);

        return view('crm.integrations.meta-capi', compact('integration', 'settings'));
    }

    public function update(Request $request, MetaConversionsApiService $capi)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->metaIntegration($request);
        if (! $integration) {
            return redirect()->route('crm.integrations.index')
                ->with('error', 'Connect Meta Ads before configuring Conversions API.');
        }

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'dataset_id' => ['nullable', 'string', 'max:64'],
            'lead_event_source' => ['nullable', 'string', 'max:120'],
            'test_event_code' => ['nullable', 'string', 'max:120'],
            'capi_access_token' => ['nullable', 'string', 'max:512'],
        ]);

        $capi->saveSettings($integration, [
            'enabled' => $request->boolean('enabled'),
            'dataset_id' => $data['dataset_id'] ?? '',
            'lead_event_source' => $data['lead_event_source'] ?? 'GrowLead',
            'test_event_code' => $data['test_event_code'] ?? '',
            'capi_access_token' => $data['capi_access_token'] ?? '',
        ]);

        return redirect()->route('crm.integrations.meta.capi')
            ->with('success', 'Conversions API settings saved.');
    }

    public function test(Request $request, MetaConversionsApiService $capi)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->metaIntegration($request);
        if (! $integration) {
            return redirect()->route('crm.integrations.index')
                ->with('error', 'Connect Meta Ads before testing Conversions API.');
        }

        $data = $request->validate([
            'test_event_code' => ['nullable', 'string', 'max:120'],
        ]);

        // Persist test code if provided so Events Manager matches.
        if (! empty($data['test_event_code'])) {
            $capi->saveSettings($integration, ['test_event_code' => $data['test_event_code']]);
            $integration = $integration->fresh();
        }

        if (! $capi->datasetId($integration) || ! $capi->hasAccessTokenConfigured($integration)) {
            return back()->with('error', 'Dataset ID and CAPI Access Token are required before sending a test event.');
        }

        $result = $capi->sendTestEvent($integration, null, $data['test_event_code'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function logs(Request $request)
    {
        $this->authorize('viewAny', Integration::class);
        $org = $request->user()->organization;
        $events = MetaCapiEvent::forOrganization($org->id)
            ->with(['lead:id,first_name,last_name,email,external_id'])
            ->latest('id')
            ->paginate(40);

        return view('crm.integrations.meta-capi-logs', compact('events'));
    }

    public function retry(Request $request, MetaCapiEvent $metaCapiEvent)
    {
        $this->authorize('manage', Integration::class);
        $orgId = (int) $request->user()->organization_id;
        $event = $metaCapiEvent;

        if ((int) $event->organization_id !== $orgId) {
            abort(403);
        }

        if ($event->status === MetaCapiEvent::STATUS_SENT) {
            return back()->with('error', 'This event was already sent successfully. Duplicate resend is blocked.');
        }

        if ($event->is_test) {
            return back()->with('error', 'Test events cannot be retried from the log. Use Send Test Event instead.');
        }

        $event->update([
            'status' => MetaCapiEvent::STATUS_PENDING,
            'error_message' => null,
        ]);

        SendMetaCapiLeadEventJob::dispatch($event->id);

        return back()->with('success', 'CAPI event queued for retry.');
    }

    protected function metaIntegration(Request $request): ?Integration
    {
        return Integration::forOrganization($request->user()->organization_id)
            ->where('provider', 'meta')
            ->first();
    }
}
