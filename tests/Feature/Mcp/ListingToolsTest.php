<?php

use App\Enums\ListingStatus;
use App\Mcp\Servers\HarborServer;
use App\Mcp\Tools\ArchiveListingTool;
use App\Mcp\Tools\GetListingTool;
use App\Mcp\Tools\SearchListingsTool;
use App\Models\User;

test('search stays inside the workspace and honors price', function () {
    $owner = harborTeam()['owner'];

    harborListing($owner, [
        'title' => 'Oak writing desk',
        'slug' => 'oak-writing-desk',
        'price_cents' => 84_000,
        'status' => ListingStatus::Published,
    ]);

    $outsider = User::factory()->owner()->create();
    harborListing($outsider, [
        'title' => 'Oak writing desk',
        'slug' => 'oak-writing-desk',
        'price_cents' => 10_000,
        'status' => ListingStatus::Published,
    ]);

    $response = HarborServer::actingAs($owner)->tool(SearchListingsTool::class, [
        'query' => 'oak',
        'status' => 'published',
        'max_price' => 900,
    ]);

    $response->assertOk()
        ->assertStructuredContentPath('total', 1)
        ->assertStructuredContentPath('listings.0.slug', 'oak-writing-desk')
        ->assertStructuredContentPath('listings.0.price', '840.00');

    HarborServer::actingAs($owner)->tool(SearchListingsTool::class, [
        'query' => 'oak',
        'max_price' => 100,
    ])->assertStructuredContentPath('total', 0);
});

test('get listing reads by slug and hides other workspaces', function () {
    $owner = harborTeam()['owner'];
    harborListing($owner, [
        'title' => 'Brass arc lamp',
        'slug' => 'brass-arc-lamp',
        'description' => 'Unlacquered brass.',
    ]);

    HarborServer::actingAs($owner)->tool(GetListingTool::class, [
        'listing' => 'brass-arc-lamp',
    ])->assertOk()->assertSee('Unlacquered brass.');

    HarborServer::actingAs($owner)->tool(GetListingTool::class, [
        'listing' => 'missing-piece',
    ])->assertHasErrors();
});

test('archive previews before it writes and only an owner can call it', function () {
    $team = harborTeam();
    $listing = harborListing($team['owner'], [
        'title' => 'Marble side table',
        'slug' => 'marble-side-table',
        'status' => ListingStatus::Published,
    ]);

    HarborServer::actingAs($team['agent'])->tool(ArchiveListingTool::class, [
        'listing' => 'marble-side-table',
        'reason' => 'Chip in the top',
        'confirm' => true,
    ])->assertHasErrors();

    expect($listing->refresh()->status)->toBe(ListingStatus::Published);

    HarborServer::actingAs($team['owner'])->tool(ArchiveListingTool::class, [
        'listing' => $listing->slug,
        'reason' => 'Chip in the top',
    ])->assertOk()
        ->assertStructuredContentPath('committed', false)
        ->assertStructuredContentPath('requires_confirmation', true);

    expect($listing->refresh()->status)->toBe(ListingStatus::Published);

    HarborServer::actingAs($team['owner'])->tool(ArchiveListingTool::class, [
        'listing' => (string) $listing->id,
        'reason' => 'Chip in the top',
        'confirm' => true,
    ])->assertOk()
        ->assertStructuredContentPath('committed', true)
        ->assertStructuredContentPath('already_archived', false);

    expect($listing->refresh()->status)->toBe(ListingStatus::Archived);

    HarborServer::actingAs($team['owner'])->tool(ArchiveListingTool::class, [
        'listing' => $listing->slug,
        'reason' => 'Chip in the top',
        'confirm' => true,
    ])->assertStructuredContentPath('already_archived', true);

    expect($listing->refresh()->status)->toBe(ListingStatus::Archived);
});
