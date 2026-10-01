<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'status' => fake()->randomElement(['new', 'not_contacted', 'qualified', 'working_deal', 'future_prospect']),
            'priority' => 'medium',
            'lead_score' => fake()->numberBetween(20, 95),
            'sentiment' => fake()->randomElement(['positive', 'neutral', 'cold']),
            'city' => fake()->city(),
            'interested_in' => fake()->randomElement(['Plan A', 'Enterprise', 'Starter', 'Consultation']),
        ];
    }
}
