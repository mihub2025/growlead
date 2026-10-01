<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public ?User $actor = null,
        public ?string $oldStatus = null,
        public ?string $newStatus = null,
        public ?CarbonInterface $occurredAt = null
    ) {
        $this->newStatus ??= $lead->status;
        $this->occurredAt ??= now();
    }
}
