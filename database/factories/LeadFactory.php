<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'listing_id' => null,
            'assignee_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'company' => fake()->optional()->company(),
            'message' => fake()->paragraph(),
            'status' => LeadStatus::New,
            'source' => 'seed',
            'idempotency_key' => null,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => [
            'workspace_id' => $listing->workspace_id,
            'listing_id' => $listing->id,
        ]);
    }

    public function qualified(): static
    {
        return $this->state(fn (): array => [
            'status' => LeadStatus::Qualified,
        ]);
    }
}
