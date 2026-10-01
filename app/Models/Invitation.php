<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'invited_by', 'name', 'email', 'role_id', 'team_id',
        'token', 'message', 'permissions', 'accepted_at', 'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isValid(): bool
    {
        return ! $this->accepted_at && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
