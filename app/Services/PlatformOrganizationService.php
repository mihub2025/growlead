<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlatformOrganizationService
{
    public function __construct(protected OrganizationProvisioner $provisioner)
    {
    }

    public function overview(): array
    {
        $orgs = Organization::query();

        return [
            'organizations' => (clone $orgs)->count(),
            'active' => (clone $orgs)->where('status', 'active')->count(),
            'suspended' => (clone $orgs)->where('status', 'suspended')->count(),
            'users' => User::query()->where('is_super_admin', false)->count(),
            'leads' => Lead::query()->count(),
            'recent' => Organization::query()
                ->withCount(['users', 'leads', 'campaigns'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    public function paginate(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return Organization::query()
            ->withCount(['users', 'leads', 'campaigns'])
            ->with('creator')
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('industry', 'like', '%'.$search.'%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data, User $actor): User
    {
        $data['created_by_user_id'] = $actor->id;

        return $this->provisioner->createFromRegistration($data);
    }

    public function snapshot(Organization $organization): array
    {
        $organization->loadCount(['users', 'leads', 'campaigns']);
        $organization->load('creator');

        return [
            'organization' => $organization,
            'admins' => $organization->users()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'administrator'))
                ->orderBy('name')
                ->get(),
            'users' => $organization->users()->with('roles')->latest()->limit(12)->get(),
            'leads' => $organization->leads()->latest()->limit(10)->get(),
            'active_users' => $organization->users()->where('status', 'active')->count(),
        ];
    }

    public function setStatus(Organization $organization, string $status): void
    {
        $organization->update(['status' => $status]);
    }
}
