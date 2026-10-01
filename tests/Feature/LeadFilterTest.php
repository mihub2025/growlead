<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Opportunity;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(array $overrides = []): User
    {
        return app(OrganizationProvisioner::class)->createFromRegistration(array_merge([
            'name' => 'Admin',
            'organization_name' => 'Filter Org '.uniqid(),
            'industry' => 'generic',
            'email' => 'admin-'.uniqid().'@lead.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ], $overrides));
    }

    protected function lead(User $admin, array $attrs = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'organization_id' => $admin->organization_id,
            'status' => 'new',
        ], $attrs));
    }

    protected function listing(User $admin, array $query = [])
    {
        return $this->actingAs($admin)->get('/crm/leads?'.http_build_query($query));
    }

    protected function names($response): array
    {
        return collect($response->viewData('leads')->items())->pluck('first_name')->all();
    }

    public function test_listing_renders_all_filter_controls(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/crm/leads')
            ->assertOk()
            ->assertSee('Status')
            ->assertSee('Label')
            ->assertSee('Campaign')
            ->assertSee('Agent')
            ->assertSee('Last Updated')
            ->assertSee('Last Assigned')
            ->assertSee('Creation Date')
            ->assertSee('Meta Creation Date')
            ->assertSee('Deal Value')
            ->assertSee('Task Type')
            ->assertSee('Task Status')
            ->assertSee('Calls Made')
            ->assertSee('Lead Source')
            ->assertSee('Times Assigned')
            ->assertSee('Total leads');
    }

    public function test_status_filter(): void
    {
        $admin = $this->admin();
        $this->lead($admin, ['first_name' => 'QualLead', 'status' => 'qualified']);
        $this->lead($admin, ['first_name' => 'LostLead', 'status' => 'lost_deal']);

        $response = $this->listing($admin, ['status' => ['qualified']]);
        $response->assertOk();
        $this->assertEquals(['QualLead'], $this->names($response));
        $this->assertEquals(['QualLead'], $this->names($this->actingAs($admin)->get('/crm/leads?status[]=qualified')));
    }

    public function test_multiple_statuses_use_or_logic(): void
    {
        $admin = $this->admin();
        $this->lead($admin, ['first_name' => 'QualLead', 'status' => 'qualified']);
        $this->lead($admin, ['first_name' => 'WorkLead', 'status' => 'working_deal']);
        $this->lead($admin, ['first_name' => 'LostLead', 'status' => 'lost_deal']);

        $response = $this->listing($admin, ['status' => ['qualified', 'working_deal']]);
        $names = $this->names($response);
        $this->assertContains('QualLead', $names);
        $this->assertContains('WorkLead', $names);
        $this->assertNotContains('LostLead', $names);
    }

    public function test_label_filter(): void
    {
        $admin = $this->admin();
        $tag = Tag::forOrganization($admin->organization_id)->where('name', 'Hot Lead')->first();
        $hot = $this->lead($admin, ['first_name' => 'HotOne']);
        $hot->tags()->attach($tag->id);
        $this->lead($admin, ['first_name' => 'ColdOne']);

        $response = $this->listing($admin, ['labels' => [$tag->id]]);
        $this->assertEquals(['HotOne'], $this->names($response));
    }

    public function test_campaign_filter(): void
    {
        $admin = $this->admin();
        $campaign = Campaign::factory()->create(['organization_id' => $admin->organization_id, 'name' => 'Alpha Campaign']);
        $other = Campaign::factory()->create(['organization_id' => $admin->organization_id, 'name' => 'Beta Campaign']);
        $this->lead($admin, ['first_name' => 'AlphaLead', 'campaign_id' => $campaign->id]);
        $this->lead($admin, ['first_name' => 'BetaLead', 'campaign_id' => $other->id]);

        $response = $this->listing($admin, ['campaigns' => [$campaign->id]]);
        $this->assertEquals(['AlphaLead'], $this->names($response));
    }

    public function test_agent_filter(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->create(['organization_id' => $admin->organization_id, 'status' => 'active']);
        $this->lead($admin, ['first_name' => 'MineLead', 'assigned_user_id' => $agent->id]);
        $this->lead($admin, ['first_name' => 'OtherLead', 'assigned_user_id' => $admin->id]);

        $response = $this->listing($admin, ['agents' => [$agent->id]]);
        $this->assertEquals(['MineLead'], $this->names($response));
    }

    public function test_unassigned_filter(): void
    {
        $admin = $this->admin();
        $this->lead($admin, ['first_name' => 'FreeLead', 'assigned_user_id' => null]);
        $this->lead($admin, ['first_name' => 'TakenLead', 'assigned_user_id' => $admin->id]);

        $response = $this->listing($admin, ['include_unassigned' => 1]);
        $this->assertEquals(['FreeLead'], $this->names($response));
    }

    public function test_agent_and_unassigned_use_or_logic(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->create(['organization_id' => $admin->organization_id]);
        $this->lead($admin, ['first_name' => 'FreeLead', 'assigned_user_id' => null]);
        $this->lead($admin, ['first_name' => 'AgentLead', 'assigned_user_id' => $agent->id]);
        $this->lead($admin, ['first_name' => 'AdminLead', 'assigned_user_id' => $admin->id]);

        $response = $this->listing($admin, ['agents' => [$agent->id], 'include_unassigned' => 1]);
        $names = $this->names($response);
        $this->assertContains('FreeLead', $names);
        $this->assertContains('AgentLead', $names);
        $this->assertNotContains('AdminLead', $names);
    }

    public function test_last_updated_filter(): void
    {
        $admin = $this->admin();
        $old = $this->lead($admin, ['first_name' => 'OldLead']);
        $fresh = $this->lead($admin, ['first_name' => 'FreshLead']);
        DB::table('leads')->where('id', $old->id)->update(['updated_at' => now()->subHours(5)]);
        DB::table('leads')->where('id', $fresh->id)->update(['updated_at' => now()->subMinutes(20)]);

        $response = $this->listing($admin, [
            'last_updated_operator' => 'more',
            'last_updated_value' => 2,
            'last_updated_unit' => 'hours',
        ]);
        $this->assertEquals(['OldLead'], $this->names($response));
    }

    public function test_last_assigned_filter(): void
    {
        $admin = $this->admin();
        $old = $this->lead($admin, ['first_name' => 'OldAssign', 'assigned_user_id' => $admin->id]);
        $fresh = $this->lead($admin, ['first_name' => 'NewAssign', 'assigned_user_id' => $admin->id]);
        DB::table('leads')->where('id', $old->id)->update(['last_assigned_at' => now()->subDays(3)]);
        DB::table('leads')->where('id', $fresh->id)->update(['last_assigned_at' => now()->subHours(1)]);

        $response = $this->listing($admin, [
            'last_assigned_operator' => 'more',
            'last_assigned_value' => 1,
            'last_assigned_unit' => 'days',
        ]);
        $this->assertEquals(['OldAssign'], $this->names($response));
    }

    public function test_assignment_is_tracked_on_reassign(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->create(['organization_id' => $admin->organization_id]);
        $lead = $this->lead($admin, ['assigned_user_id' => $admin->id]);
        $this->assertSame(1, (int) $lead->fresh()->times_assigned);
        $this->assertNotNull($lead->fresh()->last_assigned_at);

        $lead->update(['assigned_user_id' => $agent->id]);
        $lead->refresh();
        $this->assertSame(2, (int) $lead->times_assigned);
        $this->assertSame($agent->id, $lead->assignments()->latest('id')->first()->to_user_id);
    }

    public function test_creation_date_this_month(): void
    {
        $admin = $this->admin();
        $current = $this->lead($admin, ['first_name' => 'ThisMonth']);
        $old = $this->lead($admin, ['first_name' => 'LastYear']);
        DB::table('leads')->where('id', $old->id)->update([
            'created_at' => now()->subMonths(2)->startOfMonth(),
            'updated_at' => now()->subMonths(2)->startOfMonth(),
        ]);

        $response = $this->listing($admin, ['creation_date' => 'this_month']);
        $names = $this->names($response);
        $this->assertContains('ThisMonth', $names);
        $this->assertNotContains('LastYear', $names);
        $this->assertNotNull($current->id);
    }

    public function test_custom_creation_date(): void
    {
        $admin = $this->admin();
        $in = $this->lead($admin, ['first_name' => 'InRange']);
        $out = $this->lead($admin, ['first_name' => 'OutRange']);
        DB::table('leads')->where('id', $in->id)->update(['created_at' => '2026-03-10 12:00:00', 'updated_at' => '2026-03-10 12:00:00']);
        DB::table('leads')->where('id', $out->id)->update(['created_at' => '2026-01-01 12:00:00', 'updated_at' => '2026-01-01 12:00:00']);

        $response = $this->listing($admin, [
            'creation_date' => 'custom',
            'creation_from' => '2026-03-01',
            'creation_to' => '2026-03-31',
        ]);
        $this->assertEquals(['InRange'], $this->names($response));
    }

    public function test_meta_creation_date(): void
    {
        $admin = $this->admin();
        $this->lead($admin, ['first_name' => 'MetaOld', 'meta_created_at' => now()->subDays(20)]);
        $this->lead($admin, ['first_name' => 'MetaNew', 'meta_created_at' => now()->subHours(2)]);

        $response = $this->listing($admin, ['meta_creation_date' => 'today']);
        $this->assertEquals(['MetaNew'], $this->names($response));
    }

    public function test_deal_value_filter_uses_opportunities(): void
    {
        $admin = $this->admin();
        $cheap = $this->lead($admin, ['first_name' => 'CheapLead', 'max_budget' => 50]);
        $rich = $this->lead($admin, ['first_name' => 'RichLead', 'max_budget' => 50]);
        Opportunity::factory()->create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $cheap->id,
            'estimated_value' => 200,
        ]);
        Opportunity::factory()->create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $rich->id,
            'estimated_value' => 5000,
        ]);

        $response = $this->listing($admin, ['deal_value_from' => 100, 'deal_value_to' => 1000]);
        $this->assertEquals(['CheapLead'], $this->names($response));
    }

    public function test_task_type_filter(): void
    {
        $admin = $this->admin();
        $callLead = $this->lead($admin, ['first_name' => 'CallLead']);
        $mailLead = $this->lead($admin, ['first_name' => 'MailLead']);
        Task::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $callLead->id,
            'title' => 'Call now',
            'type' => 'call',
            'status' => 'open',
            'due_at' => now()->addHour(),
        ]);
        Task::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $mailLead->id,
            'title' => 'Email now',
            'type' => 'email',
            'status' => 'open',
            'due_at' => now()->addHour(),
        ]);

        $response = $this->listing($admin, ['task_types' => ['call']]);
        $this->assertEquals(['CallLead'], $this->names($response));
    }

    public function test_task_due_overdue_and_no_tasks(): void
    {
        $admin = $this->admin();
        $due = $this->lead($admin, ['first_name' => 'DueLead']);
        $over = $this->lead($admin, ['first_name' => 'OverLead']);
        $none = $this->lead($admin, ['first_name' => 'NoneLead']);
        Task::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $due->id,
            'title' => 'Due task',
            'type' => 'call',
            'status' => 'open',
            'due_at' => now()->addDay(),
        ]);
        Task::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $over->id,
            'title' => 'Overdue task',
            'type' => 'call',
            'status' => 'open',
            'due_at' => now()->subDay(),
        ]);

        $this->assertEquals(['DueLead'], $this->names($this->listing($admin, ['task_status' => ['due']])));
        $this->assertEquals(['OverLead'], $this->names($this->listing($admin, ['task_status' => ['overdue']])));
        $this->assertEquals(['NoneLead'], $this->names($this->listing($admin, ['task_status' => ['no_tasks_set']])));
    }

    public function test_calls_made_filter(): void
    {
        $admin = $this->admin();
        $busy = $this->lead($admin, ['first_name' => 'BusyLead']);
        $quiet = $this->lead($admin, ['first_name' => 'QuietLead']);
        foreach (range(1, 3) as $i) {
            LeadActivity::create([
                'organization_id' => $admin->organization_id,
                'lead_id' => $busy->id,
                'type' => 'call',
                'activity_at' => now(),
            ]);
        }
        LeadActivity::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $quiet->id,
            'type' => 'call',
            'activity_at' => now(),
        ]);

        $response = $this->listing($admin, ['calls_operator' => 'more', 'calls_value' => 2]);
        $this->assertEquals(['BusyLead'], $this->names($response));
    }

    public function test_lead_source_filter(): void
    {
        $admin = $this->admin();
        $meta = $admin->organization->leadSources()->where('slug', 'meta')->first();
        $tiktok = $admin->organization->leadSources()->where('slug', 'tiktok')->first();
        $this->lead($admin, ['first_name' => 'FbLead', 'source_id' => $meta->id]);
        $this->lead($admin, ['first_name' => 'TtLead', 'source_id' => $tiktok->id]);

        $response = $this->listing($admin, ['sources' => [$meta->id]]);
        $this->assertEquals(['FbLead'], $this->names($response));
    }

    public function test_times_assigned_filter(): void
    {
        $admin = $this->admin();
        $many = $this->lead($admin, ['first_name' => 'ManyAssign', 'assigned_user_id' => $admin->id]);
        $once = $this->lead($admin, ['first_name' => 'OnceAssign', 'assigned_user_id' => $admin->id]);
        $many->forceFill(['times_assigned' => 5])->saveQuietly();
        $once->forceFill(['times_assigned' => 1])->saveQuietly();

        $response = $this->listing($admin, ['times_assigned_operator' => 'more', 'times_assigned_value' => 2]);
        $this->assertEquals(['ManyAssign'], $this->names($response));
    }

    public function test_multiple_simultaneous_filters_use_and_logic(): void
    {
        $admin = $this->admin();
        $tag = Tag::forOrganization($admin->organization_id)->where('name', 'Hot Lead')->first();
        $campaign = Campaign::factory()->create(['organization_id' => $admin->organization_id]);
        $source = $admin->organization->leadSources()->where('slug', 'meta')->first();
        $match = $this->lead($admin, [
            'first_name' => 'ComboMatch',
            'status' => 'qualified',
            'campaign_id' => $campaign->id,
            'assigned_user_id' => $admin->id,
            'source_id' => $source->id,
        ]);
        $match->tags()->attach($tag->id);
        $this->lead($admin, [
            'first_name' => 'ComboMiss',
            'status' => 'qualified',
            'campaign_id' => $campaign->id,
            'assigned_user_id' => $admin->id,
            'source_id' => $source->id,
        ]);

        $response = $this->listing($admin, [
            'status' => ['qualified'],
            'labels' => [$tag->id],
            'campaigns' => [$campaign->id],
            'agents' => [$admin->id],
            'sources' => [$source->id],
        ]);
        $this->assertEquals(['ComboMatch'], $this->names($response));
    }

    public function test_search_works_with_filters(): void
    {
        $admin = $this->admin();
        $this->lead($admin, ['first_name' => 'UniqueName', 'status' => 'qualified']);
        $this->lead($admin, ['first_name' => 'UniqueName', 'status' => 'lost_deal']);
        $this->lead($admin, ['first_name' => 'OtherName', 'status' => 'qualified']);

        $response = $this->listing($admin, ['search' => 'UniqueName', 'status' => ['qualified']]);
        $this->assertEquals(['UniqueName'], $this->names($response));
        $response->assertSee('matching leads');
    }

    public function test_pagination_preserves_filters(): void
    {
        $admin = $this->admin();
        Lead::factory()->count(15)->create([
            'organization_id' => $admin->organization_id,
            'status' => 'qualified',
            'first_name' => 'Paged',
        ]);
        $this->lead($admin, ['first_name' => 'SkipMe', 'status' => 'lost_deal']);

        $response = $this->listing($admin, ['status' => ['qualified'], 'per_page' => 10, 'page' => 2]);
        $response->assertOk();
        $leads = $response->viewData('leads');
        $this->assertSame(2, $leads->currentPage());
        $this->assertSame(15, $leads->total());
        $this->assertStringContainsString('status', urldecode($leads->url(1)));
        $this->assertNotContains('SkipMe', $this->names($response));
    }

    public function test_filters_are_organization_isolated(): void
    {
        $a = $this->admin(['email' => 'a@lead.test', 'organization_name' => 'Org A']);
        $b = $this->admin(['email' => 'b@lead.test', 'organization_name' => 'Org B']);
        $this->lead($b, ['first_name' => 'SecretLead', 'status' => 'qualified']);
        $this->lead($a, ['first_name' => 'VisibleLead', 'status' => 'qualified']);

        $response = $this->listing($a, ['status' => ['qualified']]);
        $names = $this->names($response);
        $this->assertContains('VisibleLead', $names);
        $this->assertNotContains('SecretLead', $names);
        $this->actingAs($b)->get('/crm/leads/'.Lead::where('first_name', 'VisibleLead')->first()->id)->assertForbidden();
    }

    public function test_clear_all_is_shown_only_when_filters_are_active(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/crm/leads')->assertOk()->assertDontSee('Clear All');
        $this->listing($admin, ['status' => ['qualified']])->assertSee('Clear All');
    }
}
