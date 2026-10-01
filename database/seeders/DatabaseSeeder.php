<?php

namespace Database\Seeders;

use App\Models\AutomationRule;
use App\Models\Campaign;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNote;
use App\Models\Offering;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(OrganizationProvisioner::class);

        $admin = $provisioner->createFromRegistration([
            'name' => 'Ali Raza',
            'organization_name' => 'Northstar Growth',
            'industry' => 'generic',
            'email' => 'admin@campaignpilot.test',
            'phone' => '+15550001111',
            'country' => 'US',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $org = $admin->organization;
        $roles = $org->roles()->get()->keyBy('slug');
        $sources = $org->leadSources;
        $pipeline = $org->defaultPipeline();
        $stages = $pipeline?->stages ?? collect();
        $tags = $org->tags ?? \App\Models\Tag::forOrganization($org->id)->get();

        $managers = User::factory()->count(2)->create(['organization_id' => $org->id]);
        foreach ($managers as $manager) {
            $manager->roles()->attach($roles['manager']->id);
        }

        $agents = User::factory()->count(8)->create(['organization_id' => $org->id]);
        foreach ($agents as $agent) {
            $agent->roles()->attach($roles['agent']->id);
        }

        $allUsers = User::forOrganization($org->id)->get();
        $teams = Team::factory()->count(3)->create(['organization_id' => $org->id, 'manager_id' => $managers->first()->id]);
        foreach ($teams as $i => $team) {
            $team->users()->sync($agents->skip($i * 2)->take(3)->pluck('id'));
        }

        $campaigns = Campaign::factory()->count(10)->create([
            'organization_id' => $org->id,
            'created_by' => $admin->id,
            'source_id' => $sources->random()->id,
        ]);
        $campaigns[0]->update(['status' => 'paused']);
        $campaigns[1]->update(['status' => 'archived']);
        $campaigns[2]->update(['status' => 'paused']);
        foreach ($campaigns as $campaign) {
            $campaign->users()->sync($agents->random(3)->pluck('id'));
        }

        $agents[0]->update(['status' => 'paused']);
        $agents[1]->update(['status' => 'archived']);

        Offering::create(['organization_id' => $org->id, 'name' => 'Starter Plan', 'type' => 'product', 'price' => 99, 'currency' => 'USD', 'status' => 'active']);
        Offering::create(['organization_id' => $org->id, 'name' => 'Growth Plan', 'type' => 'product', 'price' => 299, 'currency' => 'USD', 'status' => 'active']);

        $leads = collect();
        for ($i = 0; $i < 160; $i++) {
            $user = $allUsers->random();
            $stage = $stages->random();
            $lead = Lead::factory()->create([
                'organization_id' => $org->id,
                'source_id' => $sources->random()->id,
                'campaign_id' => $campaigns->random()->id,
                'pipeline_id' => $pipeline?->id,
                'pipeline_stage_id' => $stage?->id,
                'assigned_user_id' => $i % 8 === 0 ? null : $user->id,
                'assigned_team_id' => $teams->random()->id,
                'created_by' => $admin->id,
                'currency' => 'USD',
                'status' => fake()->randomElement(array_column(config('crm.default_lead_statuses'), 'slug')),
                'min_budget' => 1000,
                'max_budget' => 25000,
                'last_activity_at' => now()->subHours(rand(1, 72)),
                'sla_deadline_at' => now()->addMinutes(15),
                'meta_created_at' => now()->subDays(rand(0, 21)),
            ]);
            if ($tags->isNotEmpty()) {
                $lead->tags()->sync($tags->random(min(2, $tags->count()))->pluck('id'));
            }
            LeadActivity::create([
                'organization_id' => $org->id,
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'type' => 'lead_created',
                'subject' => 'Lead created',
                'activity_at' => $lead->created_at,
            ]);
            if ($i % 4 === 0) {
                LeadNote::create(['organization_id' => $org->id, 'lead_id' => $lead->id, 'user_id' => $user->id, 'note' => 'Follow up after first conversation.']);
                Task::create([
                    'organization_id' => $org->id,
                    'lead_id' => $lead->id,
                    'assigned_user_id' => $user->id,
                    'created_by' => $admin->id,
                    'type' => fake()->randomElement(['call', 'email', 'whatsapp', 'text', 'meeting']),
                    'title' => 'Call '.$lead->full_name,
                    'priority' => 'high',
                    'status' => 'open',
                    'due_at' => now()->addHours(rand(-6, 24)),
                ]);
            }
            if ($i % 5 === 0) {
                LeadActivity::create([
                    'organization_id' => $org->id,
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'type' => 'call',
                    'subject' => 'Outbound call',
                    'activity_at' => now()->subHours(rand(1, 48)),
                ]);
            }
            $leads->push($lead);
        }

        foreach ($leads->take(35) as $lead) {
            Opportunity::factory()->create([
                'organization_id' => $org->id,
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'assigned_user_id' => $lead->assigned_user_id,
                'status' => fake()->randomElement(['open', 'open', 'won', 'lost']),
            ]);
        }

        AutomationRule::create([
            'organization_id' => $org->id,
            'name' => 'Create follow-up on new lead',
            'trigger' => 'lead_created',
            'actions' => [['type' => 'create_task', 'title' => 'First contact', 'due_hours' => 2]],
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        foreach (['meta' => 'Meta Ads', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $provider => $name) {
            Integration::create([
                'organization_id' => $org->id,
                'provider' => $provider,
                'name' => $name,
                'status' => 'disconnected',
            ]);
        }

        $this->call(SuperAdminSeeder::class);

        $this->command?->info('Demo org admin: admin@campaignpilot.test / password');
    }
}
