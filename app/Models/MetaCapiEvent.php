<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaCapiEvent extends Model
{
    use BelongsToOrganization;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'organization_id', 'integration_id', 'lead_id', 'meta_lead_id',
        'from_status', 'crm_status', 'meta_event_name', 'event_id',
        'event_time', 'status', 'attempts', 'response_code',
        'response_body_sanitized', 'error_message', 'is_test', 'sent_at',
    ];

    protected $casts = [
        'event_time' => 'integer',
        'attempts' => 'integer',
        'response_code' => 'integer',
        'response_body_sanitized' => 'array',
        'is_test' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_PENDING], true)
            && ! $this->is_test;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_SKIPPED => 'Skipped',
            default => 'Pending',
        };
    }
}
