<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true).' Campaign',
            'objective' => 'leads',
            'status' => 'active',
            'budget' => 10000,
            'total_spend' => 2500,
            'total_leads' => 20,
            'qualified_leads' => 6,
            'routing_method' => 'round_robin',
        ];
    }
}
