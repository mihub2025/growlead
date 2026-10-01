<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookLead;
use App\Models\Organization;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLeadController extends Controller
{
    public function store(Request $request, string $source)
    {
        $token = $request->header('X-Webhook-Token') ?: $request->query('token');
        $organization = Organization::where('slug', $request->header('X-Organization') ?: $request->query('org'))->first();

        if (! $organization || ! $token || $token !== data_get($organization->settings, 'webhook_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $log = WebhookLog::create([
            'organization_id' => $organization->id,
            'source' => $source,
            'payload' => $request->all(),
            'status' => 'queued',
            'ip' => $request->ip(),
        ]);
        ProcessWebhookLead::dispatch($log);

        return response()->json(['ok' => true, 'id' => $log->id]);
    }
}
