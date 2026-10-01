<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function admin()
    {
        return app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Lead Org',
            'industry' => 'generic',
            'email' => 'admin@lead.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
    }

    public function test_admin_can_create_and_view_lead(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/crm/leads', [
            'first_name' => 'Sam',
            'last_name' => 'Lee',
            'email' => 'sam@example.com',
            'phone' => '5551112222',
        ])->assertRedirect();

        $lead = Lead::first();
        $this->assertSame($admin->organization_id, $lead->organization_id);
        $this->actingAs($admin)->get('/crm/leads/'.$lead->id)->assertOk();
    }

    public function test_organization_data_is_isolated(): void
    {
        $a = $this->admin();
        $b = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Other',
            'organization_name' => 'Other Org',
            'industry' => 'generic',
            'email' => 'other@lead.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $lead = Lead::factory()->create(['organization_id' => $a->organization_id]);

        $this->actingAs($b)->get('/crm/leads/'.$lead->id)->assertForbidden();
    }

    public function test_search_filters_leads(): void
    {
        $admin = $this->admin();
        Lead::factory()->create(['organization_id' => $admin->organization_id, 'first_name' => 'UniqueName']);
        $this->actingAs($admin)->get('/crm/leads?search=UniqueName')->assertOk()->assertSee('UniqueName');
    }

    public function test_listing_drops_checkbox_sentiment_and_activity_columns(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create([
            'organization_id' => $admin->organization_id,
            'first_name' => 'Listing',
            'last_name' => 'Lead',
            'sentiment' => 'positive',
        ]);

        $this->actingAs($admin)->get('/crm/leads')
            ->assertOk()
            ->assertDontSee('data-check-all', false)
            ->assertDontSee('data-row-check', false)
            ->assertDontSee('<th>Sentiment</th>', false)
            ->assertDontSee('<th>Last Activity</th>', false)
            ->assertDontSee('<th>Next Task</th>', false)
            ->assertSee('Activity')
            ->assertSee(route('crm.leads.activity', $lead), false);
    }

    public function test_lead_view_shows_sentiment(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create([
            'organization_id' => $admin->organization_id,
            'sentiment' => 'positive',
        ]);

        $this->actingAs($admin)->get('/crm/leads/'.$lead->id)
            ->assertOk()
            ->assertSee('Sentiment')
            ->assertSee('Positive');
    }

    public function test_activity_view_shows_agent_actions_last_activity_and_next_task(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create(['organization_id' => $admin->organization_id]);

        \App\Models\LeadActivity::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'type' => 'call',
            'subject' => 'Discovery Call',
            'description' => 'Reached the customer',
            'activity_at' => now(),
        ]);

        \App\Models\Task::create([
            'organization_id' => $admin->organization_id,
            'lead_id' => $lead->id,
            'assigned_user_id' => $admin->id,
            'title' => 'Follow up tomorrow',
            'status' => 'open',
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)->get('/crm/leads/'.$lead->id.'/activity')
            ->assertOk()
            ->assertSee('Last Activity')
            ->assertSee('Next Task')
            ->assertSee('Discovery Call')
            ->assertSee('Reached the customer')
            ->assertSee('Follow up tomorrow')
            ->assertSee($admin->name);
    }
}
