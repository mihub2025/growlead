<?php

namespace App\Providers;

use App\Models\AutomationRule;
use App\Models\Campaign;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\AutomationPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\LeadPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lead::class => LeadPolicy::class,
        Campaign::class => CampaignPolicy::class,
        User::class => UserPolicy::class,
        Team::class => TeamPolicy::class,
        AutomationRule::class => AutomationPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdministrator() && ! str_contains($ability, '.')) {
                return null;
            }

            return null;
        });

        foreach (config('crm.permissions') as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
