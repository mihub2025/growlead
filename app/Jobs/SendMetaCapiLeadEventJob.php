<?php

namespace App\Jobs;

use App\Models\MetaCapiEvent;
use App\Services\Integrations\MetaConversionsApiService;
use App\Support\MetaCapiResponseSanitizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendMetaCapiLeadEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** @var list<int> */
    public array $backoff = [30, 120, 300, 900, 1800];

    public function __construct(public int $metaCapiEventId)
    {
        $this->tries = max(1, (int) config('meta.capi.retry_attempts', 5));
    }

    public function handle(MetaConversionsApiService $capi): void
    {
        $event = MetaCapiEvent::find($this->metaCapiEventId);
        if (! $event) {
            return;
        }

        if ($event->status === MetaCapiEvent::STATUS_SENT) {
            return;
        }

        $capi->sendQueuedEvent($event);
    }

    public function failed(?Throwable $exception): void
    {
        $event = MetaCapiEvent::find($this->metaCapiEventId);
        if (! $event || $event->status === MetaCapiEvent::STATUS_SENT) {
            return;
        }

        $event->update([
            'status' => MetaCapiEvent::STATUS_FAILED,
            'error_message' => $exception
                ? MetaCapiResponseSanitizer::message($exception->getMessage())
                : $event->error_message,
        ]);
    }
}
