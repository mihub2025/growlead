<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToOrganization, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'organization_id', 'name', 'email', 'phone', 'avatar', 'status', 'is_super_admin',
        'password', 'invitation_token', 'invited_at', 'last_active_at', 'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'invited_at' => 'datetime',
        'last_active_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('role_in_team');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withPivot('weight');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_user_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_user_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function primaryRole(): ?Role
    {
        return $this->roles->first();
    }

    public function roleName(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        return $this->primaryRole()?->name ?? 'User';
    }

    public function roleSlug(): string
    {
        return $this->primaryRole()?->slug ?? 'viewer';
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator');
    }

    public function homeRouteName(): string
    {
        return $this->isSuperAdmin() ? 'super.dashboard' : 'crm.dashboard';
    }

    public function hasRole(string $slug): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains('slug', $slug);
    }

    public function hasPermission(string $permission): bool
    {
        $this->loadMissing('roles.permissions');

        if ($this->isSuperAdmin()) {
            return false;
        }

        if ($this->roles->contains('slug', 'administrator')) {
            return true;
        }

        return $this->roles->contains(function (Role $role) use ($permission) {
            return $role->permissions->contains('slug', $permission);
        });
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = collect($parts)->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');

        return strtoupper($letters ?: 'U');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
