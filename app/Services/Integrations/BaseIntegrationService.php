<?php

namespace App\Services\Integrations;

use App\Models\Integration;

abstract class BaseIntegrationService
{
    public function __construct(protected Integration $integration)
    {
    }

    public function isReady(): bool
    {
        return $this->integration->isConnected();
    }

    public function sync(): array
    {
        if (! $this->isReady()) {
            return ['ok' => false, 'message' => $this->integration->name.' integration required'];
        }

        $this->integration->update(['last_synced_at' => now(), 'status' => 'connected', 'last_error' => null]);

        return ['ok' => true, 'message' => 'Sync queued'];
    }
}
