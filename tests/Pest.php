<?php

use App\Models\Listing;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * @return array{workspace: Workspace, owner: User, agent: User, viewer: User}
 */
function harborTeam(): array
{
    $workspace = Workspace::factory()->create([
        'name' => 'Northline Studio',
        'slug' => 'northline',
    ]);

    $owner = User::factory()->owner()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Avery Chen',
        'email' => 'owner@harborboard.test',
    ]);

    $agent = User::factory()->agent()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Jordan Hale',
        'email' => 'agent@harborboard.test',
    ]);

    $viewer = User::factory()->viewer()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Sam Okonkwo',
        'email' => 'viewer@harborboard.test',
    ]);

    return compact('workspace', 'owner', 'agent', 'viewer');
}

/**
 * @param  array<string, mixed>  $overrides
 */
function harborListing(User $author, array $overrides = []): Listing
{
    return Listing::factory()->create(array_merge([
        'workspace_id' => $author->workspace_id,
        'created_by' => $author->id,
    ], $overrides));
}
