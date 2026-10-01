<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'first_name', 'last_name', 'email', 'phone',
        'phone_normalized', 'whatsapp', 'whatsapp_normalized', 'alternate_phone',
        'company', 'job_title', 'source_id', 'campaign_id', 'pipeline_id',
        'pipeline_stage_id', 'status', 'priority', 'assigned_user_id',
        'assigned_team_id', 'co_assigned_user_id', 'interested_in', 'category',
        'requirement', 'quantity', 'purpose', 'decision_timeline', 'min_budget',
        'max_budget', 'currency', 'country', 'city', 'area', 'latitude',
        'longitude', 'preferred_contact_method', 'preferred_language',
        'preferred_contact_time', 'medium', 'utm_source', 'utm_medium',
        'utm_campaign', 'email_opt_in', 'sms_opt_in', 'whatsapp_opt_in',
        'do_not_contact', 'external_id', 'lead_score', 'sentiment',
        'engagement_score', 'conversion_probability', 'sla_status',
        'sla_deadline_at', 'first_response_at', 'last_activity_at',
        'next_followup_at', 'meta_created_at', 'last_assigned_at',
        'times_assigned', 'created_by', 'notes', 'meta_payload',
    ];

    protected $casts = [
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'email_opt_in' => 'boolean',
        'sms_opt_in' => 'boolean',
        'whatsapp_opt_in' => 'boolean',
        'do_not_contact' => 'boolean',
        'sla_deadline_at' => 'datetime',
        'first_response_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'meta_created_at' => 'datetime',
        'last_assigned_at' => 'datetime',
        'times_assigned' => 'integer',
        'meta_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            $lead->uuid ??= (string) Str::uuid();
            $lead->phone_normalized = self::normalizePhone($lead->phone);
            $lead->whatsapp_normalized = self::normalizePhone($lead->whatsapp ?: $lead->phone);
        });

        static::updating(function (Lead $lead) {
            if ($lead->isDirty('phone')) {
                $lead->phone_normalized = self::normalizePhone($lead->phone);
            }
            if ($lead->isDirty('whatsapp')) {
                $lead->whatsapp_normalized = self::normalizePhone($lead->whatsapp);
            }
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits ?: null;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'Unnamed Lead';
    }

    public function getLocationAttribute(): string
    {
        return collect([$this->area, $this->city, $this->country])->filter()->implode(', ');
    }

    public function getBudgetLabelAttribute(): string
    {
        $currency = $this->organization?->currencyCode() ?: 'USD';
        if ($this->min_budget && $this->max_budget) {
            return $currency.' '.number_format((float) $this->min_budget, 0).' - '.number_format((float) $this->max_budget, 0);
        }
        if ($this->max_budget) {
            return $currency.' '.number_format((float) $this->max_budget, 0);
        }

        return '—';
    }

    public function statusLabel(): string
    {
        $status = (string) $this->status;
        foreach (config('crm.default_lead_statuses', []) as $row) {
            if (strcasecmp((string) $row['slug'], $status) === 0 || strcasecmp((string) $row['name'], $status) === 0) {
                return $row['name'];
            }
            foreach ($row['aliases'] ?? [] as $alias) {
                if (strcasecmp((string) $alias, $status) === 0) {
                    return $row['name'];
                }
            }
        }
        if ($this->stage) {
            return $this->stage->name;
        }

        return $status ? Str::headline(str_replace(['_', '-'], ' ', $status)) : '—';
    }

    public function statusBadgeClass(): string
    {
        $slug = strtolower((string) $this->status);
        if ($this->do_not_contact) {
            return 'badge-neutral';
        }
        if (in_array($slug, ['deal_closed', 'won', 'closed-won', 'closed_won', 'qualified', 'working_deal'], true)) {
            return 'badge-active';
        }
        if (in_array($slug, ['lost_deal', 'lost', 'closed-lost', 'closed_lost', 'unqualified'], true)) {
            return 'badge-neutral';
        }
        if (in_array($slug, ['not_contacted', 'new', 'did_not_respond'], true)) {
            return 'badge-done';
        }

        return 'badge-warm';
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function coAssignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('activity_at');
    }

    public function notesList(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(LeadInterest::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function customValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'valuable');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(DuplicateCandidate::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function nextTask(): ?Task
    {
        return $this->tasks()->where('status', '!=', 'completed')->orderBy('due_at')->first();
    }

    public function initials(): string
    {
        return strtoupper(mb_substr($this->first_name ?? 'L', 0, 1).mb_substr($this->last_name ?? '', 0, 1));
    }

    public function sentimentKey(): string
    {
        return strtolower((string) ($this->sentiment ?: 'neutral'));
    }

    public function sentimentLabel(): string
    {
        return ucfirst($this->sentimentKey());
    }

    public function sentimentClass(): string
    {
        return match ($this->sentimentKey()) {
            'positive' => 'sent-pos',
            'negative', 'cold' => 'sent-neg',
            default => 'sent-neu',
        };
    }

    public function sentimentEmoji(): string
    {
        return match ($this->sentimentKey()) {
            'positive' => '😊',
            'negative', 'cold' => '😞',
            default => '😐',
        };
    }

    public function scoreLabel(): string
    {
        $score = (int) $this->lead_score;
        if ($score >= 80) {
            return 'High Intent';
        }
        if ($score >= 60) {
            return 'Qualified';
        }
        if ($score >= 40) {
            return 'Warm';
        }

        return 'Cold';
    }

    public function isMetaLead(): bool
    {
        $slug = strtolower((string) ($this->source?->slug ?? ''));

        return filled($this->metaLeadId())
            || $slug === 'meta'
            || strtolower((string) $this->utm_source) === 'facebook'
            || $this->metaFormPayload() !== [];
    }

    /**
     * Original Meta Instant Form leadgen_id used for CRM Conversions API matching.
     */
    public function metaLeadId(): ?string
    {
        $fromPayload = data_get($this->metaFormPayload(), 'lead_id');
        if (filled($fromPayload)) {
            return (string) $fromPayload;
        }

        if (filled($this->external_id) && (
            strtolower((string) ($this->source?->slug ?? '')) === 'meta'
            || strtolower((string) $this->utm_source) === 'facebook'
            || $this->metaFormPayload() !== []
        )) {
            return (string) $this->external_id;
        }

        // Prefer external_id when it looks like a Meta lead id (15-17 digits).
        if (filled($this->external_id) && preg_match('/^\d{15,17}$/', (string) $this->external_id)) {
            return (string) $this->external_id;
        }

        return null;
    }

    public function metaFormPayload(): array
    {
        return is_array($this->meta_payload) ? $this->meta_payload : [];
    }

    public function metaFormRows(): array
    {
        $payload = $this->metaFormPayload();
        $rows = [];
        $seen = [];

        foreach ($payload['field_data'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = (string) ($item['name'] ?? '');
            if ($key === '') {
                continue;
            }
            $value = $item['values'] ?? '';
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map('strval', $value), fn ($part) => $part !== ''));
            }
            $rows[] = [
                'label' => Str::headline(str_replace('_', ' ', $key)),
                'value' => $value !== '' ? (string) $value : '—',
                'key' => $key,
            ];
            $seen[$key] = true;
        }

        if ($rows === []) {
            foreach ([
                ['Full Name', $this->full_name, 'full_name'],
                ['Email', $this->email, 'email'],
                ['Phone Number', $this->phone, 'phone_number'],
            ] as [$label, $value, $key]) {
                if (filled($value) && $value !== 'Unnamed Lead') {
                    $rows[] = ['label' => $label, 'value' => $value, 'key' => $key];
                    $seen[$key] = true;
                }
            }
        }

        $submittedAt = $this->meta_created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A')
            ?: ($payload['created_time'] ?? null);

        foreach ([
            ['Form Name', $payload['form_name'] ?? $this->interested_in, 'form_name'],
            ['Source', $this->source?->name ?? 'Meta Lead Ads', 'source'],
            ['Page', $payload['page_name'] ?? $this->metaNoteValue('Page'), 'page'],
            ['Campaign', $this->campaign?->name ?? ($payload['campaign_name'] ?? null), 'campaign_id'],
            ['Ad Set', $payload['adset_name'] ?? $payload['adset_id'] ?? null, 'adset_id'],
            ['Ad', $payload['ad_name'] ?? $payload['ad_id'] ?? null, 'ad_id'],
            ['Submission Time', $submittedAt, 'created_time'],
        ] as [$label, $value, $key]) {
            if (isset($seen[$key]) || ! filled($value)) {
                continue;
            }
            $rows[] = ['label' => $label, 'value' => (string) $value, 'key' => $key];
        }

        return $rows;
    }

    protected function metaNoteValue(string $label): ?string
    {
        if (! filled($this->notes)) {
            return null;
        }
        if (preg_match('/^'.preg_quote($label, '/').':\s*(.+)$/mi', (string) $this->notes, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
