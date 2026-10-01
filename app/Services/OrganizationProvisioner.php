<?php

namespace App\Services;

use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

class OrganizationProvisioner
{
    public function createFromRegistration(array $data): User
    {
        $organization = Organization::create([
            'name' => $data['organization_name'],
            'slug' => $this->uniqueSlug($data['organization_name']),
            'industry' => $data['industry'] ?? 'generic',
            'currency' => $data['currency'] ?? 'USD',
            'country' => $data['country'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'phone_country' => $data['phone_country'] ?? $data['country'] ?? null,
            'status' => 'active',
            'sla_minutes' => config('crm.sla_minutes', 15),
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'settings' => [
                'duplicate_detection' => true,
                'merge_suggestions' => true,
                'auto_merge' => false,
                'webhook_token' => Str::random(40),
            ],
        ]);

        $this->seedPermissions();
        $roles = $this->seedRoles($organization);
        $this->seedSources($organization);
        $this->seedPipeline($organization);
        $this->seedTags($organization);
        $this->seedIndustryFields($organization);

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($roles['administrator']->id);

        return $user;
    }

    public function seedPermissions(): void
    {
        foreach (config('crm.permissions') as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => Str::headline(str_replace('.', ' ', $slug)), 'group' => Str::before($slug, '.')]
            );
        }
    }

    public function seedRoles(Organization $organization): array
    {
        $permissions = Permission::all()->keyBy('slug');
        $created = [];

        $map = [
            'administrator' => $permissions->keys()->all(),
            'manager' => [
                'dashboard.view', 'leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.export',
                'campaigns.view', 'campaigns.create', 'campaigns.edit', 'reports.view', 'reports.export',
                'users.view', 'users.create', 'users.edit', 'teams.manage', 'automations.view',
                'integrations.view', 'settings.view', 'opportunities.view', 'opportunities.manage', 'ai.view',
            ],
            'team_lead' => [
                'dashboard.view', 'leads.view', 'leads.create', 'leads.edit', 'leads.assign',
                'campaigns.view', 'reports.view', 'users.view', 'opportunities.view', 'opportunities.manage', 'ai.view',
            ],
            'agent' => [
                'dashboard.view', 'leads.view', 'leads.create', 'leads.edit',
                'campaigns.view', 'opportunities.view', 'ai.view',
            ],
            'viewer' => [
                'dashboard.view', 'leads.view', 'campaigns.view', 'reports.view',
            ],
        ];

        foreach (config('crm.roles') as $slug) {
            $role = Role::firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $slug],
                ['name' => Str::headline($slug), 'is_system' => true]
            );
            $permissionIds = collect($map[$slug] ?? [])
                ->map(fn (string $permSlug) => $permissions->get($permSlug)?->id)
                ->filter()
                ->values()
                ->all();
            $role->permissions()->sync($permissionIds);
            $created[$slug] = $role;
        }

        return $created;
    }

    public function repairEmptyRolePermissions(Organization $organization): void
    {
        $empty = Role::query()
            ->where('organization_id', $organization->id)
            ->whereDoesntHave('permissions')
            ->exists();

        if ($empty) {
            $this->seedRoles($organization);
        }
    }

    public function seedSources(Organization $organization): void
    {
        foreach (config('crm.default_sources') as $source) {
            LeadSource::firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $source['slug']],
                $source + ['organization_id' => $organization->id, 'status' => 'active']
            );
        }
    }

    public function seedPipeline(Organization $organization): Pipeline
    {
        $pipeline = Pipeline::firstOrCreate(
            ['organization_id' => $organization->id, 'is_default' => true],
            ['name' => 'Default Pipeline', 'entity_type' => 'lead', 'status' => 'active']
        );

        foreach (config('crm.default_pipeline_stages') as $index => $stage) {
            PipelineStage::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'slug' => $stage['slug']],
                $stage + ['pipeline_id' => $pipeline->id, 'position' => $index + 1, 'active' => true]
            );
        }

        return $pipeline;
    }

    public function seedTags(Organization $organization): void
    {
        foreach (config('crm.default_tags') as $tag) {
            Tag::firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $tag['name']],
                $tag + ['organization_id' => $organization->id, 'active' => true]
            );
        }
    }

    public function seedIndustryFields(Organization $organization): void
    {
        $templates = [
            'real_estate' => [
                ['name' => 'Property Type', 'slug' => 'property_type', 'type' => 'dropdown', 'options' => ['House', 'Apartment', 'Villa', 'Plot', 'Commercial']],
                ['name' => 'Bedrooms', 'slug' => 'bedrooms', 'type' => 'dropdown', 'options' => ['Studio', '1', '2', '3', '4', '5+']],
                ['name' => 'Area', 'slug' => 'area_size', 'type' => 'text'],
                ['name' => 'Possession Date', 'slug' => 'possession_date', 'type' => 'date'],
            ],
            'automotive' => [
                ['name' => 'Make', 'slug' => 'make', 'type' => 'text'],
                ['name' => 'Model', 'slug' => 'model', 'type' => 'text'],
                ['name' => 'Year', 'slug' => 'year', 'type' => 'number'],
                ['name' => 'Vehicle Type', 'slug' => 'vehicle_type', 'type' => 'dropdown', 'options' => ['Sedan', 'SUV', 'Hatchback', 'Truck', 'Other']],
            ],
            'education' => [
                ['name' => 'Program', 'slug' => 'program', 'type' => 'text'],
                ['name' => 'Intake', 'slug' => 'intake', 'type' => 'text'],
                ['name' => 'Campus', 'slug' => 'campus', 'type' => 'text'],
                ['name' => 'Qualification', 'slug' => 'qualification', 'type' => 'text'],
            ],
            'insurance' => [
                ['name' => 'Policy Type', 'slug' => 'policy_type', 'type' => 'text'],
                ['name' => 'Coverage', 'slug' => 'coverage', 'type' => 'text'],
                ['name' => 'Existing Policy', 'slug' => 'existing_policy', 'type' => 'toggle'],
            ],
            'solar' => [
                ['name' => 'Property Type', 'slug' => 'property_type', 'type' => 'text'],
                ['name' => 'Electricity Bill', 'slug' => 'electricity_bill', 'type' => 'currency'],
                ['name' => 'Desired System Size', 'slug' => 'system_size', 'type' => 'text'],
            ],
        ];

        $fields = $templates[$organization->industry] ?? [];
        foreach ($fields as $index => $field) {
            $organization->customFields()->firstOrCreate(
                ['slug' => $field['slug'], 'entity_type' => 'lead'],
                [
                    'section' => 'Interest',
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'position' => $index + 1,
                    'active' => true,
                ]
            );
        }
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $i = 1;
        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
