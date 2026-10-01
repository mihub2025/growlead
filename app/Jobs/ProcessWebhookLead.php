<?php

namespace App\Jobs;

use App\Events\CampaignLeadReceived;
use App\Models\Campaign;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\WebhookLog;
use App\Services\LeadRoutingService;
use App\Services\LeadService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookLog $log)
    {
    }

    public function handle(LeadService $leads, LeadRoutingService $routing): void
    {
        $log = $this->log->fresh();
        $payload = $log->payload ?? [];
        $organization = Organization::find($log->organization_id);
        if (! $organization) {
            $log->update(['status' => 'failed', 'error' => 'Organization not found', 'processed_at' => now()]);
            return;
        }

        $source = LeadSource::forOrganization($organization->id)->where('slug', $log->source)->first();
        $campaign = ! empty($payload['campaign_id'])
            ? Campaign::forOrganization($organization->id)->find($payload['campaign_id'])
            : null;

        $lead = $leads->create($organization, [
            'first_name' => $payload['first_name'] ?? $payload['name'] ?? 'Webhook',
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'whatsapp' => $payload['whatsapp'] ?? $payload['phone'] ?? null,
            'source_id' => $source?->id,
            'campaign_id' => $campaign?->id,
            'interested_in' => $payload['interested_in'] ?? null,
            'city' => $payload['city'] ?? null,
            'external_id' => $payload['external_id'] ?? $payload['id'] ?? null,
            'meta_created_at' => $this->parseMetaCreatedAt($payload),
        ]);

        if ($campaign) {
            $routing->assign($lead, $campaign);
            CampaignLeadReceived::dispatch($lead, $campaign);
        }

        $log->update(['status' => 'processed', 'result' => 'lead:'.$lead->id, 'processed_at' => now()]);
    }

    protected function parseMetaCreatedAt(array $payload): ?Carbon
    {
        $raw = $payload['created_time'] ?? $payload['created_at'] ?? $payload['meta_created_at'] ?? $payload['created'] ?? null;
        if (! $raw) {
            return null;
        }
        try {
            return is_numeric($raw) ? Carbon::createFromTimestamp((int) $raw) : Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
