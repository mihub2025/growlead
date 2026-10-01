<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'provider', 'name', 'status', 'credentials',
        'settings', 'last_synced_at', 'last_error',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
