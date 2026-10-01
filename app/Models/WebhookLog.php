<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'source', 'payload', 'status', 'result', 'error', 'ip', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
