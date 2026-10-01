<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_currency_is_used_across_the_portal(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Currency Org',
            'industry' => 'generic',
            'email' => 'admin@currency.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
        $org = $admin->organization;

        Campaign::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Spend Campaign',
            'budget' => 300,
            'total_spend' => 27290,
            'currency' => 'USD',
        ]);

        $this->actingAs($admin)
            ->get('/crm/campaigns')
            ->assertOk()
            ->assertSee('USD 27,290')
            ->assertSee('USD 300');

        $this->actingAs($admin)->put('/crm/settings/organization', [
            'name' => $org->name,
            'industry' => $org->industry,
            'currency' => 'PKR',
            'country' => $org->country,
            'timezone' => $org->timezone ?: 'UTC',
            'language' => $org->language,
            'phone_country' => $org->phone_country,
            'date_format' => $org->date_format,
            'sla_minutes' => $org->sla_minutes ?: 15,
        ])->assertRedirect();

        $this->assertSame('PKR', $org->fresh()->currency);
        $this->assertDatabaseHas('campaigns', [
            'organization_id' => $org->id,
            'currency' => 'PKR',
        ]);

        $this->actingAs($admin)
            ->get('/crm/campaigns')
            ->assertOk()
            ->assertSee('PKR 27,290')
            ->assertSee('PKR 300')
            ->assertDontSee('USD 27,290');
    }
}
