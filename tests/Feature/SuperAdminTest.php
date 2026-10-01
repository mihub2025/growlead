<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        return User::factory()->superAdmin()->create([
            'name' => 'Platform Super Admin',
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);
    }

    protected function orgAdmin(): User
    {
        return app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Jane Admin',
            'organization_name' => 'Northstar Growth',
            'industry' => 'generic',
            'email' => 'jane@example.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);
    }

    public function test_public_registration_cannot_create_an_organization(): void
    {
        $this->get('/register')->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Jane Admin',
            'organization_name' => 'Acme Corp',
            'industry' => 'generic',
            'email' => 'jane@example.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseMissing('organizations', ['name' => 'Acme Corp']);
    }

    public function test_super_admin_login_opens_the_platform_console(): void
    {
        $this->superAdmin();

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertRedirect('/super/dashboard');
    }

    public function test_organization_admin_cannot_open_the_platform_console(): void
    {
        $admin = $this->orgAdmin();

        $this->actingAs($admin)->get('/super/dashboard')->assertRedirect('/crm/dashboard');
        $this->actingAs($admin)->get('/super/organizations/create')->assertRedirect('/crm/dashboard');
        $this->actingAs($admin)->post('/super/organizations', [
            'organization_name' => 'Rogue Org',
            'industry' => 'generic',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'name' => 'Rogue',
            'email' => 'rogue@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/crm/dashboard');

        $this->assertDatabaseMissing('organizations', ['name' => 'Rogue Org']);
    }

    public function test_super_admin_cannot_use_the_organization_crm(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->get('/crm/dashboard')->assertRedirect('/super/dashboard');
    }

    public function test_super_admin_can_create_and_monitor_an_organization(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->post('/super/organizations', [
                'organization_name' => 'Harbor Realty',
                'industry' => 'real_estate',
                'country' => 'PK',
                'currency' => 'PKR',
                'timezone' => 'Asia/Karachi',
                'name' => 'Sana Ahmed',
                'email' => 'sana@harbor.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organizations', ['name' => 'Harbor Realty', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['email' => 'sana@harbor.test', 'is_super_admin' => 0]);

        $organization = Organization::query()->where('name', 'Harbor Realty')->first();
        $this->assertSame($super->id, $organization->created_by_user_id);

        $this->actingAs($super)
            ->get('/super/organizations/'.$organization->id)
            ->assertOk()
            ->assertSee('Harbor Realty')
            ->assertSee('Sana Ahmed');
    }

    public function test_super_admin_can_suspend_an_organization(): void
    {
        $super = $this->superAdmin();
        $admin = $this->orgAdmin();

        $this->actingAs($super)
            ->put('/super/organizations/'.$admin->organization_id.'/status', ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame('suspended', $admin->organization->fresh()->status);

        $this->post('/logout');
        $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_mobile_api_rejects_super_admin_login(): void
    {
        $this->superAdmin();

        $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertStatus(422);
    }
}
