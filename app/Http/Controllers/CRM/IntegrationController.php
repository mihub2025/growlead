<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIntegrationRequest;
use App\Models\Integration;
use App\Models\WebhookLog;
use App\Services\Integrations\MetaOAuthService;
use App\Services\Integrations\MetaSyncService;
use Illuminate\Http\Request;
use Throwable;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Integration::class);
        $org = $request->user()->organization;
        $providers = ['meta' => 'Meta Ads', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'google' => 'Google', 'linkedin' => 'LinkedIn', 'website' => 'Website Forms', 'webhook' => 'Webhook/API', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'bayut' => 'Bayut', 'dubizzle' => 'Dubizzle'];
        $integrations = Integration::forOrganization($org->id)->get()->keyBy('provider');
        $logs = WebhookLog::forOrganization($org->id)->latest()->limit(50)->get();
        $metaOAuth = app(MetaOAuthService::class);
        $metaConfigured = $metaOAuth->configured();
        $metaRedirectUri = $metaOAuth->redirectUri();

        return view('crm.integrations.index', compact('providers', 'integrations', 'logs', 'metaConfigured', 'metaRedirectUri'));
    }

    public function connectMeta(Request $request, MetaOAuthService $meta)
    {
        $this->authorize('manage', Integration::class);
        if (! $meta->configured()) {
            return redirect()->route('crm.integrations.index')->with(
                'error',
                'Meta OAuth is not configured. Add META_APP_ID and META_APP_SECRET to .env. Redirect URI: '.$meta->redirectUri()
            );
        }

        return redirect()->away($meta->authorizationUrl($meta->makeState()));
    }

    public function callbackMeta(Request $request, MetaOAuthService $meta)
    {
        $this->authorize('manage', Integration::class);

        if ($request->filled('error')) {
            return redirect()->route('crm.integrations.index', ['oauth' => 1])
                ->with('error', $request->string('error_description')->toString() ?: 'Meta connection was cancelled.');
        }

        $state = (string) $request->get('state');
        if (! $state || $state !== session('meta_oauth_state')) {
            return redirect()->route('crm.integrations.index', ['oauth' => 1])
                ->with('error', 'Meta connection failed (invalid state). Click Connect and try again.');
        }
        session()->forget('meta_oauth_state');

        $code = trim((string) $request->get('code'));
        if ($code === '') {
            return redirect()->route('crm.integrations.index', ['oauth' => 1])
                ->with('error', 'Meta did not return an authorization code.');
        }

        try {
            $integration = $meta->complete($request->user(), $code);
        } catch (Throwable $e) {
            Integration::updateOrCreate(
                ['organization_id' => $request->user()->organization_id, 'provider' => 'meta'],
                ['name' => 'Meta Ads', 'status' => 'error', 'last_error' => $e->getMessage()]
            );

            return redirect()->route('crm.integrations.index', ['oauth' => 1])
                ->with('error', 'Meta connection failed: '.$e->getMessage());
        }

        return $this->afterMetaConnected($integration, app(MetaSyncService::class));
    }

    public function businessesMeta(Request $request, MetaSyncService $sync)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->connectedMeta($request);
        if (! $integration) {
            return redirect()->route('crm.integrations.index')->with('error', 'Connect Meta Ads first.');
        }

        $warnings = [];
        try {
            $businesses = $sync->listBusinesses($integration, $warnings, true);
        } catch (Throwable $e) {
            return redirect()->route('crm.integrations.index')->with('error', $e->getMessage());
        }

        if ($businesses === []) {
            return redirect()->route('crm.integrations.index')->with('error', 'No Business Managers or ad accounts were found for this Facebook login.');
        }

        $selected = $sync->selectedBusinessIds($integration);

        return view('crm.integrations.meta-businesses', compact('integration', 'businesses', 'selected', 'warnings'));
    }

    public function saveBusinessesMeta(Request $request, MetaSyncService $sync)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->connectedMeta($request);
        if (! $integration) {
            return redirect()->route('crm.integrations.index')->with('error', 'Connect Meta Ads first.');
        }

        $ids = $request->validate([
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['string', 'max:64'],
        ])['business_ids'];

        $warnings = [];
        try {
            $catalog = $sync->listBusinesses($integration, $warnings);
            $sync->saveSelectedBusinesses($integration, $ids, $catalog);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $integration = $integration->fresh();

        return redirect()->route('crm.integrations.index')
            ->with('success', $this->runMetaSync($integration, $sync));
    }

    public function syncMeta(Request $request, MetaSyncService $sync)
    {
        $this->authorize('manage', Integration::class);
        $integration = $this->connectedMeta($request);

        if (! $integration) {
            return back()->with('error', 'Connect Meta Ads before syncing campaigns and leads.');
        }

        try {
            if ($sync->needsBusinessPicker($integration)) {
                return redirect()->route('crm.integrations.meta.businesses')
                    ->with('error', 'Select which Facebook businesses to sync.');
            }
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $this->runMetaSync($integration, $sync));
    }

    public function disconnectMeta(Request $request, MetaOAuthService $meta)
    {
        $this->authorize('manage', Integration::class);
        $meta->disconnect($request->user());

        return back()->with('success', 'Meta Ads disconnected.');
    }

    public function store(StoreIntegrationRequest $request)
    {
        $data = $request->validated();
        if (($data['provider'] ?? '') === 'meta') {
            return back()->with('error', 'Connect Meta Ads with the Connect button.');
        }
        $token = trim((string) data_get($data, 'credentials.token', ''));
        if ($token === '') {
            unset($data['credentials']);
        }

        Integration::updateOrCreate(
            ['organization_id' => $request->user()->organization_id, 'provider' => $data['provider']],
            $data + ['organization_id' => $request->user()->organization_id]
        );

        return back()->with('success', 'Integration saved. Secrets are encrypted and never displayed.');
    }

    protected function afterMetaConnected(Integration $integration, MetaSyncService $sync)
    {
        $warnings = [];
        try {
            $catalog = $sync->listBusinesses($integration, $warnings, true);
        } catch (Throwable $e) {
            $integration->update(['last_error' => $e->getMessage()]);

            return redirect()->route('crm.integrations.index', ['oauth' => 1])
                ->with('error', 'Meta connected, but Facebook businesses could not be listed: '.$e->getMessage());
        }

        if (count($catalog) > 1) {
            $sync->saveSelectedBusinesses($integration, $sync->selectedBusinessIds($integration), $catalog);

            return redirect()->route('crm.integrations.meta.businesses', ['oauth' => 1]);
        }

        if (count($catalog) === 1) {
            $sync->saveSelectedBusinesses($integration, [(string) $catalog[0]['id']], $catalog);
            $integration = $integration->fresh();
        }

        return redirect()->route('crm.integrations.index', ['oauth' => 1])
            ->with('success', $this->runMetaSync($integration, $sync, justConnected: true));
    }

    protected function connectedMeta(Request $request): ?Integration
    {
        $integration = Integration::forOrganization($request->user()->organization_id)
            ->where('provider', 'meta')
            ->first();

        if (! $integration || $integration->status !== 'connected') {
            return null;
        }

        return $integration;
    }

    protected function runMetaSync(Integration $integration, ?MetaSyncService $sync = null, bool $justConnected = false): string
    {
        $sync ??= app(MetaSyncService::class);

        try {
            $summary = $sync->sync($integration);
        } catch (Throwable $e) {
            $integration->update(['last_error' => $e->getMessage()]);

            return ($justConnected ? 'Meta connected, but Facebook sync failed: ' : 'Facebook sync failed: ').$e->getMessage();
        }

        $campaigns = (int) ($summary['campaigns_imported'] ?? 0);
        $leads = (int) ($summary['leads_imported'] ?? 0);
        $message = ($justConnected ? 'Meta Ads connected. ' : '')
            ."Synced {$campaigns} campaign".($campaigns === 1 ? '' : 's')
            ." and {$leads} new lead".($leads === 1 ? '' : 's').'.';

        if (! empty($summary['warnings'])) {
            $message .= ' '.implode(' ', array_slice($summary['warnings'], 0, 2));
        }

        return $message;
    }
}
