<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\GenericCrmNotification;
use Illuminate\Console\Command;

class MonitorSla extends Command
{
    protected $signature = 'crm:monitor-sla';
    protected $description = 'Mark SLA warning and breach statuses';

    public function handle(): int
    {
        Lead::whereNull('first_response_at')->whereNotNull('sla_deadline_at')->chunkById(200, function ($leads) {
            foreach ($leads as $lead) {
                $minutes = now()->diffInMinutes($lead->created_at, false) * -1;
                $limit = $lead->organization?->sla_minutes ?? 15;
                $status = $minutes >= $limit ? 'breached' : ($minutes >= ($limit * 0.7) ? 'warning' : 'safe');
                if ($lead->sla_status !== $status) {
                    $lead->update(['sla_status' => $status]);
                    if (in_array($status, ['warning', 'breached'], true)) {
                        $lead->assignedUser?->notify(new GenericCrmNotification(
                            $status === 'breached' ? 'SLA Breach' : 'SLA Warning',
                            'Lead '.$lead->full_name.' needs a first response.'
                        ));
                    }
                }
            }
        });

        return self::SUCCESS;
    }
}
