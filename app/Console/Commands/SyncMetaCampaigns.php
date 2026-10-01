<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Integrations\MetaSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncMetaCampaigns extends Command
{
    protected $signature = 'crm:sync-meta {organization?}';
    protected $description = 'Pull Facebook campaigns and Lead Ads into GrowLead';

    public function handle(MetaSyncService $sync): int
    {
        $query = Integration::query()->where('provider', 'meta')->where('status', 'connected');
        if ($this->argument('organization')) {
            $query->where('organization_id', $this->argument('organization'));
        }

        $integrations = $query->get();
        if ($integrations->isEmpty()) {
            $this->info('No connected Meta integrations.');

            return self::SUCCESS;
        }

        foreach ($integrations as $integration) {
            try {
                $summary = $sync->sync($integration);
                $this->info(sprintf(
                    'Org %s: %s campaigns, %s new leads',
                    $integration->organization_id,
                    $summary['campaigns_imported'] ?? 0,
                    $summary['leads_imported'] ?? 0
                ));
            } catch (Throwable $e) {
                $integration->update(['last_error' => $e->getMessage()]);
                $this->error('Org '.$integration->organization_id.': '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
