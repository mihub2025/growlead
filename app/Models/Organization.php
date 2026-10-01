<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'industry', 'logo', 'currency', 'country', 'timezone',
        'language', 'date_format', 'phone_country', 'status', 'sla_minutes', 'settings',
        'created_by_user_id',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function leadSources(): HasMany
    {
        return $this->hasMany(LeadSource::class);
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(Pipeline::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function defaultPipeline(): ?Pipeline
    {
        return $this->pipelines()->where('is_default', true)->first();
    }

    public function currencyCode(): string
    {
        $code = strtoupper(trim((string) $this->currency));

        return $code !== '' ? $code : 'USD';
    }

    public function formatMoney(?float $amount): string
    {
        return $this->currencyCode().' '.number_format((float) $amount, 0);
    }

    public function propagateCurrency(?string $currency = null): void
    {
        $currency = strtoupper(trim((string) ($currency ?? $this->currencyCode())));
        if ($this->currency !== $currency) {
            $this->update(['currency' => $currency]);
        }

        $this->campaigns()->update(['currency' => $currency]);
        $this->leads()->update(['currency' => $currency]);
        Opportunity::forOrganization($this->id)->update(['currency' => $currency]);
        Offering::forOrganization($this->id)->update(['currency' => $currency]);
    }
}
