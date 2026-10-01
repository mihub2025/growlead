<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'estimated_value' => fake()->numberBetween(1000, 50000),
            'probability' => fake()->numberBetween(20, 90),
            'status' => 'open',
            'expected_close_date' => now()->addDays(14),
        ];
    }
}
