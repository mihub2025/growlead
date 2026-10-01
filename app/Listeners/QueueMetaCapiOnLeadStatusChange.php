<?php

namespace App\Listeners;

use App\Events\LeadStatusChanged;
use App\Jobs\SendMetaCapiLeadEventJob;
use App\Models\MetaCapiEvent;
use App\Services\Integrations\MetaConversionsApiService;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueMetaCapiOnLeadStatusChange
{
    public function __construct(protected MetaConversionsApiService $capi) {}

    public function handle(LeadStatusChanged $event): void
    {
        try {
            $lead = $event->lead->fresh() ?? $event->lead;
            $old = $this->capi->normalizeStatusSlug((string) ($event->oldStatus ?? ''));
            $new = $this->capi->normalizeStatusSlug((string) ($event->newStatus ?? $lead->status ?? ''));

            if ($new === '' || $old === $new) {
                return;
            }

            if (! $this->capi->shouldAutoSendStatus($new)) {
                return;
            }

            $metaEventName = $this->capi->eventNameForStatus($new);
            if (! $metaEventName) {
                return;
            }

            $integration = $this->capi->metaIntegrationForOrganization((int) $lead->organization_id);
            if (! $integration || ! $this->capi->isEnabled($integration)) {
                return;
            }

            $occurredAt = $event->occurredAt ?? now();
            $eventTime = $occurredAt->getTimestamp();

            // Ensure event_time is after lead generation when Meta lead time is known.
            if ($lead->meta_created_at && $eventTime <= $lead->meta_created_at->getTimestamp()) {
                $eventTime = $lead->meta_created_at->getTimestamp() + 1;
            }

            $record = MetaCapiEvent::create([
                'organization_id' => $lead->organization_id,
                'integration_id' => $integration->id,
                'lead_id' => $lead->id,
                'meta_lead_id' => $lead->metaLeadId(),
                'from_status' => $old !== '' ? $old : null,
                'crm_status' => $new,
                'meta_event_name' => $metaEventName,
                'event_id' => 'pending:'.$lead->organization_id.':'.$lead->id.':'.$new.':'.uniqid('', true),
                'event_time' => $eventTime,
                'status' => MetaCapiEvent::STATUS_PENDING,
                'attempts' => 0,
                'is_test' => false,
            ]);

            // Deterministic id after we have the transition row primary key.
            $deterministicId = $this->capi->makeEventId(
                (int) $lead->organization_id,
                (int) $lead->id,
                $new,
                (int) $record->id
            );
            $record->update(['event_id' => $deterministicId]);

            SendMetaCapiLeadEventJob::dispatch($record->id);
        } catch (Throwable $e) {
            // Never block CRM status updates because of CAPI plumbing.
            Log::warning('meta_capi.queue_failed', [
                'lead_id' => $event->lead->id ?? null,
                'organization_id' => $event->lead->organization_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
