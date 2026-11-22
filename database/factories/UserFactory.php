<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'workspace_id' => Workspace::factory(),
            'role' => WorkspaceRole::Agent,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => WorkspaceRole::Owner,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn (): array => [
            'role' => WorkspaceRole::Agent,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (): array => [
            'role' => WorkspaceRole::Viewer,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
