<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'industry' => 'generic',
            'currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
            'status' => 'active',
            'sla_minutes' => 15,
            'settings' => ['webhook_token' => Str::random(40)],
        ];
    }
}
