<?php

namespace App\Jobs;

use App\Models\CsvImport;
use App\Models\LeadSource;
use App\Notifications\GenericCrmNotification;
use App\Services\LeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public CsvImport $import)
    {
    }

    public function handle(LeadService $leads): void
    {
        $import = $this->import->fresh();
        $import->update(['status' => 'processing']);
        $path = Storage::path($import->stored_path);
        if (! is_file($path)) {
            $import->update(['status' => 'failed', 'error' => 'File missing']);
            return;
        }

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle) ?: [];
        $map = $import->column_map ?? [];
        $source = LeadSource::forOrganization($import->organization_id)->where('slug', 'csv')->first();
        $imported = 0;
        $skipped = 0;
        $total = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $data = [];
            foreach ($map as $field => $index) {
                $data[$field] = $row[$index] ?? null;
            }
            if (empty($data['first_name']) && empty($data['email']) && empty($data['phone'])) {
                $skipped++;
                continue;
            }
            $data['campaign_id'] = $import->campaign_id;
            $data['source_id'] = $source?->id;
            $leads->create($import->organization, $data, $import->user);
            $imported++;
        }
        fclose($handle);

        $import->update([
            'status' => 'completed',
            'total_rows' => $total,
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
        ]);

        $import->user?->notify(new GenericCrmNotification(
            'CSV Import Complete',
            "Imported {$imported} leads, skipped {$skipped}."
        ));
    }
}
