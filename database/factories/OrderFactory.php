<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'listing_id' => Listing::factory(),
            'lead_id' => Lead::factory(),
            'placed_by' => User::factory(),
            'number' => 'HB-'.fake()->unique()->numerify('####'),
            'amount_cents' => fake()->numberBetween(5_000, 100_000),
            'currency' => 'USD',
            'status' => OrderStatus::Pending,
            'notes' => null,
            'confirmed_at' => now(),
        ];
    }
}
