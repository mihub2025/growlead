<?php

namespace Tests\Feature;

use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_routes_are_public_and_not_behind_auth(): void
    {
        foreach (['privacy-policy', 'terms', 'data-deletion'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, "Named route [{$name}] is missing.");
            $middleware = $route->gatherMiddleware();
            $this->assertNotContains('auth', $middleware);
            $this->assertNotContains('guest', $middleware);
        }
    }

    public function test_guests_can_view_all_legal_pages(): void
    {
        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy', false)
            ->assertSee('Meta Lead Ads', false)
            ->assertSee('Data Deletion', false)
            ->assertSee(config('crm.privacy_email'), false);

        $this->get('/terms')
            ->assertOk()
            ->assertSee('Terms of Service', false)
            ->assertSee('Connected Facebook/Meta Accounts', false)
            ->assertSee(config('crm.privacy_email'), false);

        $this->get('/data-deletion')
            ->assertOk()
            ->assertSee('User Data Deletion Instructions', false)
            ->assertSee('Revoking access stops future access', false)
            ->assertSee(config('crm.privacy_email'), false);
    }

    public function test_authenticated_users_can_still_view_legal_pages(): void
    {
        $admin = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Admin',
            'organization_name' => 'Legal Org',
            'industry' => 'generic',
            'email' => 'admin@legal.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy');

        $this->actingAs($admin)->get('/terms')->assertOk()->assertSee('Terms of Service');
        $this->actingAs($admin)->get('/data-deletion')->assertOk()->assertSee('User Data Deletion Instructions');
    }

    public function test_crm_prefixed_aliases_are_public(): void
    {
        $this->get('/crm/privacy-policy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/crm/terms')->assertOk()->assertSee('Terms of Service');
        $this->get('/crm/data-deletion')->assertOk()->assertSee('User Data Deletion Instructions');
    }

    public function test_named_routes_generate_root_paths(): void
    {
        $this->assertSame(url('/privacy-policy'), route('privacy-policy'));
        $this->assertSame(url('/terms'), route('terms'));
        $this->assertSame(url('/data-deletion'), route('data-deletion'));
    }
}
