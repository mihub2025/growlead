<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_closed_to_the_public(): void
    {
        $this->post('/register', [
            'name' => 'Jane Admin',
            'organization_name' => 'Acme Corp',
            'industry' => 'generic',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'country' => 'US',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseMissing('organizations', ['name' => 'Acme Corp']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = app(OrganizationProvisioner::class)->createFromRegistration([
            'name' => 'Pat',
            'organization_name' => 'Pat Org',
            'industry' => 'generic',
            'email' => 'pat@example.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'password' => 'password',
        ]);

        $this->post('/login', ['email' => 'pat@example.com', 'password' => 'password'])
            ->assertRedirect('/crm/dashboard');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_dashboard_is_protected(): void
    {
        $this->get('/crm/dashboard')->assertRedirect('/login');
    }
}
