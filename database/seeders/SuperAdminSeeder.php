<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'superadmin@campaignpilot.test'],
            [
                'organization_id' => null,
                'name' => 'Platform Super Admin',
                'password' => 'password',
                'status' => 'active',
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->is_super_admin || $user->organization_id) {
            $user->forceFill([
                'organization_id' => null,
                'is_super_admin' => true,
                'status' => 'active',
            ])->save();
        }

        $this->command?->info('Super admin: superadmin@campaignpilot.test / password');
    }
}
