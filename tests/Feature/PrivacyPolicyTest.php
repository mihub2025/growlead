<?php

namespace Tests\Feature;

use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_privacy_policy_without_logging_in(): void
    {
        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy', false)
            ->assertSee('Meta Lead Ads', false)
            ->assertSee('Data Deletion', false);
    }

    public function test_authenticated_users_can_still_view_the_privacy_policy(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Privacy Org',
            'industry' => 'generic',
            'email' => 'admin@privacy.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy');
    }
}
