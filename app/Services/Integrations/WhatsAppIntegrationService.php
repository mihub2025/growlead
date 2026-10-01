<?php

namespace App\Services\Integrations;

class WhatsAppIntegrationService extends BaseIntegrationService
{
    public function send(string $to, string $message): array
    {
        if (! $this->isReady()) {
            return ['ok' => false, 'message' => 'WhatsApp integration required'];
        }

        return ['ok' => false, 'message' => 'WhatsApp provider credentials are not fully configured'];
    }
}
