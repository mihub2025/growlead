<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'name', 'source_id', 'integration_id', 'external_campaign_id',
        'meta_business_id', 'meta_business_name', 'meta_ad_account_id', 'meta_ad_account_name',
        'objective', 'description', 'budget_type', 'budget', 'daily_budget', 'currency',
        'start_date', 'end_date', 'status', 'routing_method', 'target_leads',
        'target_qualified_leads', 'target_cpl', 'target_revenue', 'total_spend',
        'total_leads', 'qualified_leads', 'conversions', 'revenue', 'ai_score',
        'sync_status', 'last_synced_at', 'created_by', 'wizard_step', 'settings',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'total_spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
        'settings' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('weight');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function costPerLead(): float
    {
        $leads = max(1, (int) $this->total_leads);

        return round(((float) $this->total_spend) / $leads, 2);
    }

    public function roi(): float
    {
        $spend = (float) $this->total_spend;
        if ($spend <= 0) {
            return 0;
        }

        return round(((float) $this->revenue) / $spend, 2);
    }

    public function statusGroup(): string
    {
        $status = strtolower((string) $this->status);
        foreach (config('crm.campaign_status_groups', []) as $group => $values) {
            if (in_array($status, $values, true)) {
                return $group;
            }
        }

        return 'active';
    }

    public function qualifiedRate(): float
    {
        $leads = max(1, (int) $this->total_leads);

        return round(((int) $this->qualified_leads / $leads) * 100, 1);
    }

    public function metaBusinessLabel(): ?string
    {
        $name = trim((string) $this->meta_business_name);

        return $name !== '' ? $name : null;
    }
}
