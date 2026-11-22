<?php

namespace Database\Factories;

use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => User::factory(),
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(ListingCategory::cases()),
            'price_cents' => fake()->numberBetween(8_000, 200_000),
            'currency' => 'USD',
            'status' => ListingStatus::Published,
            'location' => fake()->city().', '.fake()->stateAbbr(),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingStatus::Sold,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingStatus::Archived,
        ]);
    }
}
