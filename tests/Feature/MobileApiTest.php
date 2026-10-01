<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Notifications\GenericCrmNotification;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $agent;

    protected User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin User',
            'organization_name' => 'Northstar Growth',
            'industry' => 'generic',
            'email' => 'admin@northstar.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $agentRole = Role::query()
            ->where('organization_id', $this->admin->organization_id)
            ->where('slug', 'agent')
            ->firstOrFail();

        $this->agent = User::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'name' => 'Ali Raza',
            'email' => 'ali@northstar.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->agent->roles()->attach($agentRole->id);

        $this->otherAgent = User::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'name' => 'Sara Khan',
            'email' => 'sara@northstar.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->otherAgent->roles()->attach($agentRole->id);
    }

    protected function asAgent(?User $user = null)
    {
        $user ??= $this->agent;
        $token = $user->createToken(config('mobile.token_name'))->plainTextToken;

        return $this->withToken($token);
    }

    public function test_agent_can_login_and_fetch_profile(): void
    {
        $login = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'ali@northstar.test',
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonPath('success', true);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/mobile/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'ali@northstar.test')
            ->assertJsonMissingPath('data.user.organization_id');
    }

    public function test_agent_can_logout_and_token_is_revoked(): void
    {
        $login = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'ali@northstar.test',
            'password' => 'password',
        ]);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertSame(1, $this->agent->tokens()->count());

        $this->withToken($token)
            ->postJson('/api/mobile/v1/auth/logout')
            ->assertOk();

        $this->assertSame(0, $this->agent->tokens()->count());

        config(['sanctum.stateful' => []]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/mobile/v1/me')
            ->assertUnauthorized();
    }

    public function test_agent_only_sees_assigned_leads(): void
    {
        $mine = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
            'first_name' => 'SMR',
            'last_name' => 'Tiles',
            'status' => 'not_contacted',
        ]);
        Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->otherAgent->id,
            'first_name' => 'Hidden',
            'last_name' => 'Lead',
        ]);

        $this->asAgent()
            ->getJson('/api/mobile/v1/leads')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_agent_cannot_view_another_organizations_lead(): void
    {
        $otherAdmin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Other Admin',
            'organization_name' => 'Other Org',
            'industry' => 'generic',
            'email' => 'other@org.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $foreign = Lead::factory()->create([
            'organization_id' => $otherAdmin->organization_id,
            'assigned_user_id' => $otherAdmin->id,
        ]);

        $this->asAgent()
            ->getJson('/api/mobile/v1/leads/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_there_is_no_generic_lead_update_endpoint(): void
    {
        $lead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
            'first_name' => 'Locked',
        ]);

        $response = $this->asAgent()
            ->patchJson('/api/mobile/v1/leads/'.$lead->id, ['first_name' => 'Hacked']);

        $this->assertContains($response->status(), [404, 405]);

        $this->assertSame('Locked', $lead->fresh()->first_name);
    }

    public function test_qualified_call_activity_updates_status_through_backend(): void
    {
        $lead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
            'status' => 'not_contacted',
            'pipeline_id' => $this->admin->organization->defaultPipeline()?->id,
        ]);

        $this->asAgent()
            ->postJson('/api/mobile/v1/leads/'.$lead->id.'/activities', [
                'type' => 'call',
                'outcome' => 'qualified',
                'notes' => 'Customer confirmed budget.',
            ])
            ->assertCreated();

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'call',
            'user_id' => $this->agent->id,
        ]);
    }

    public function test_agent_can_create_standalone_and_lead_linked_tasks(): void
    {
        $lead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
        ]);

        $this->asAgent()
            ->postJson('/api/mobile/v1/tasks', [
                'title' => 'Send proposal',
                'type' => 'email',
                'priority' => 'high',
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.lead', null);

        $this->asAgent()
            ->postJson('/api/mobile/v1/tasks', [
                'title' => 'Call back',
                'lead_id' => $lead->id,
                'type' => 'call',
                'due_at' => now()->addHours(2)->toIso8601String(),
                'reminder' => '30m',
            ])
            ->assertCreated()
            ->assertJsonPath('data.lead.id', $lead->id);

        $foreignLead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->otherAgent->id,
        ]);

        $this->asAgent()
            ->postJson('/api/mobile/v1/tasks', [
                'title' => 'Should fail',
                'lead_id' => $foreignLead->id,
                'due_at' => now()->addHour()->toIso8601String(),
            ])
            ->assertForbidden();
    }

    public function test_agent_can_complete_own_task_and_schedule_follow_up(): void
    {
        $lead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
        ]);

        $create = $this->asAgent()
            ->postJson('/api/mobile/v1/tasks', [
                'title' => 'Follow up with lead',
                'lead_id' => $lead->id,
                'type' => 'follow_up',
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated();

        $taskId = $create->json('data.id');
        $this->asAgent()
            ->postJson('/api/mobile/v1/tasks/'.$taskId.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->asAgent()
            ->postJson('/api/mobile/v1/leads/'.$lead->id.'/follow-ups', [
                'type' => 'call',
                'due_at' => now()->addDay()->setTime(14, 0)->toIso8601String(),
                'notes' => 'Discuss pricing',
                'reminder' => '1h',
            ])
            ->assertCreated();

        $this->assertNotNull($lead->fresh()->next_followup_at);
    }

    public function test_notifications_can_be_listed_and_marked_read(): void
    {
        $this->agent->notify(new GenericCrmNotification(
            'New lead assigned',
            'SMR Tiles Golimar has been assigned to you.',
            null,
            'leads',
            ['lead_id' => 1]
        ));

        $list = $this->asAgent()
            ->getJson('/api/mobile/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $id = $list->json('data.0.id');
        $this->asAgent()
            ->postJson('/api/mobile/v1/notifications/'.$id.'/read')
            ->assertOk();

        $this->asAgent()
            ->getJson('/api/mobile/v1/notifications')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_assigned_user_id_from_mobile_is_ignored(): void
    {
        $this->asAgent()
            ->postJson('/api/mobile/v1/tasks', [
                'title' => 'Personal task',
                'assigned_user_id' => $this->otherAgent->id,
                'created_by' => $this->otherAgent->id,
                'organization_id' => 999,
                'due_at' => now()->addHour()->toIso8601String(),
            ])
            ->assertCreated();

        $task = Task::query()->latest('id')->first();
        $this->assertSame($this->agent->id, $task->assigned_user_id);
        $this->assertSame($this->agent->id, $task->created_by);
        $this->assertSame($this->admin->organization_id, $task->organization_id);
    }

    public function test_dashboard_includes_pipeline_productivity_and_campaigns(): void
    {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'name' => 'Prestige One',
            'status' => 'active',
        ]);
        Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
            'campaign_id' => $campaign->id,
            'status' => 'not_contacted',
            'phone' => '03001234567',
        ]);

        $this->asAgent()
            ->getJson('/api/mobile/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.pipeline.summary.0.key', 'total')
            ->assertJsonPath('data.productivity.channels.0.key', 'calls')
            ->assertJsonPath('data.campaigns.0.name', 'Prestige One');
    }

    public function test_agent_sees_only_campaigns_with_assigned_leads_and_can_start_calling(): void
    {
        $mine = Campaign::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'name' => 'Karam Phase 1',
            'status' => 'active',
        ]);
        $theirs = Campaign::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'name' => 'Hidden Campaign',
            'status' => 'active',
        ]);
        $lead = Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->agent->id,
            'campaign_id' => $mine->id,
            'first_name' => 'Ahmed',
            'status' => 'not_contacted',
            'phone' => '03001112222',
        ]);
        Lead::factory()->create([
            'organization_id' => $this->admin->organization_id,
            'assigned_user_id' => $this->otherAgent->id,
            'campaign_id' => $theirs->id,
            'status' => 'not_contacted',
            'phone' => '03003334444',
        ]);

        $list = $this->asAgent()->getJson('/api/mobile/v1/campaigns')->assertOk();
        $this->assertSame(['Karam Phase 1'], collect($list->json('data'))->pluck('name')->all());

        $this->asAgent()
            ->getJson('/api/mobile/v1/campaigns/'.$mine->id.'/next-call')
            ->assertOk()
            ->assertJsonPath('data.lead.id', $lead->id)
            ->assertJsonPath('data.remaining', 1)
            ->assertJsonStructure([
                'data' => [
                    'lead' => [
                        'id',
                        'name',
                        'phone',
                        'form_answers',
                        'status',
                    ],
                ],
            ]);

        $this->asAgent()
            ->getJson('/api/mobile/v1/campaigns/'.$theirs->id.'/next-call')
            ->assertForbidden();
    }

    public function test_agent_can_create_a_manual_lead_assigned_to_self(): void
    {
        $response = $this->asAgent()
            ->postJson('/api/mobile/v1/leads', [
                'first_name' => 'Nadia',
                'last_name' => 'Khan',
                'phone' => '03005556666',
                'assigned_user_id' => $this->otherAgent->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nadia Khan');

        $lead = Lead::query()->findOrFail($response->json('data.id'));
        $this->assertSame($this->agent->id, $lead->assigned_user_id);
        $this->assertSame('not_contacted', $lead->status);
        $this->assertSame($this->admin->organization_id, $lead->organization_id);
    }
}
