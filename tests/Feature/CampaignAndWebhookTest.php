<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAndWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_campaign(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Camp Org',
            'industry' => 'generic',
            'email' => 'admin@camp.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $this->actingAs($admin)->post('/crm/campaigns', [
            'name' => 'Spring Push',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('campaigns', ['name' => 'Spring Push', 'organization_id' => $admin->organization_id]);
    }

    public function test_webhook_requires_token(): void
    {
        $this->postJson('/api/v1/leads/webhook/website', ['first_name' => 'A'])->assertUnauthorized();
    }

    public function test_webhook_queues_valid_payload(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Hook Org',
            'industry' => 'generic',
            'email' => 'admin@hook.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $org = $admin->organization->fresh();
        $token = $org->settings['webhook_token'];

        $this->postJson('/api/v1/leads/webhook/website?token='.$token.'&org='.$org->slug, [
            'first_name' => 'Hook',
            'email' => 'hook@example.com',
        ])->assertOk();
    }

    public function test_admin_can_assign_agents_to_a_campaign(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Assign Org',
            'industry' => 'generic',
            'email' => 'admin@assign.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $agent = $this->makeAgent($admin, 'Agent One', 'agent1@assign.test');
        $campaign = Campaign::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Spring Push',
        ]);

        $this->actingAs($admin)->from('/crm/campaigns')->post('/crm/campaigns/'.$campaign->id.'/assign', [
            'user_ids' => [$agent->id],
        ])->assertRedirect('/crm/campaigns');

        $this->assertTrue($campaign->fresh()->users->contains('id', $agent->id));
        $this->actingAs($admin)->get('/crm/campaigns')
            ->assertOk()
            ->assertSee('Assign')
            ->assertSee('Agent One');
    }

    public function test_agent_only_sees_leads_from_assigned_campaigns(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Scope Org',
            'industry' => 'generic',
            'email' => 'admin@scope.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $agent = $this->makeAgent($admin, 'Ali Agent', 'ali@scope.test');
        $mine = Campaign::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Visible Camp',
        ]);
        $theirs = Campaign::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Hidden Camp',
        ]);
        $mine->users()->attach($agent->id, ['weight' => 1]);

        $visible = \App\Models\Lead::factory()->create([
            'organization_id' => $admin->organization_id,
            'campaign_id' => $mine->id,
            'first_name' => 'VisibleLead',
            'assigned_user_id' => null,
        ]);
        $hidden = \App\Models\Lead::factory()->create([
            'organization_id' => $admin->organization_id,
            'campaign_id' => $theirs->id,
            'first_name' => 'HiddenLead',
            'assigned_user_id' => $admin->id,
        ]);

        $this->actingAs($agent->fresh())->get('/crm/leads')
            ->assertOk()
            ->assertSee('VisibleLead')
            ->assertDontSee('HiddenLead');

        $this->actingAs($agent)->get('/crm/leads/'.$visible->id)->assertOk();
        $this->actingAs($agent)->get('/crm/leads/'.$hidden->id)->assertForbidden();

        $this->actingAs($agent)->get('/crm/campaigns')
            ->assertOk()
            ->assertSee('Visible Camp')
            ->assertDontSee('Hidden Camp');
    }

    protected function makeAgent($admin, string $name, string $email)
    {
        $role = \App\Models\Role::query()
            ->where('organization_id', $admin->organization_id)
            ->where('slug', 'agent')
            ->firstOrFail();
        $agent = \App\Models\User::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'status' => 'active',
        ]);
        $agent->roles()->attach($role->id);

        return $agent;
    }
}
