<?php

use App\Enums\LeadStatus;
use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Mcp\Servers\HarborServer;
use App\Mcp\Tools\PlaceOrderTool;
use App\Mcp\Tools\SearchOrdersTool;
use App\Models\Lead;
use App\Models\Order;

test('place order previews and commits only when confirmed', function () {
    $agent = harborTeam()['agent'];
    $listing = harborListing($agent, [
        'title' => 'Oak writing desk',
        'slug' => 'oak-writing-desk',
        'price_cents' => 84_000,
        'status' => ListingStatus::Published,
    ]);
    $lead = Lead::factory()->forListing($listing)->qualified()->create([
        'name' => 'Maya Ellis',
        'email' => 'maya.ellis@example.com',
        'message' => 'I want the oak writing desk at the recorded price.',
    ]);

    HarborServer::actingAs($agent)->tool(PlaceOrderTool::class, [
        'lead_id' => $lead->id,
    ])->assertOk()
        ->assertStructuredContentPath('committed', false)
        ->assertStructuredContentPath('requires_confirmation', true)
        ->assertStructuredContentPath('preview.amount', '840.00')
        ->assertStructuredContentPath('order', null);

    expect(Order::query()->count())->toBe(0)
        ->and($lead->refresh()->status)->toBe(LeadStatus::Qualified)
        ->and($listing->refresh()->status)->toBe(ListingStatus::Published);

    HarborServer::actingAs($agent)->tool(PlaceOrderTool::class, [
        'lead_id' => $lead->id,
        'notes' => 'Hold through Friday.',
        'confirm' => true,
    ])->assertOk()
        ->assertStructuredContentPath('committed', true)
        ->assertStructuredContentPath('order.amount', '840.00')
        ->assertStructuredContentPath('order.status', 'pending');

    expect($lead->refresh()->status)->toBe(LeadStatus::Converted)
        ->and($listing->refresh()->status)->toBe(ListingStatus::Sold);

    HarborServer::actingAs($agent)->tool(PlaceOrderTool::class, [
        'lead_id' => $lead->id,
        'confirm' => true,
    ])->assertHasErrors();

    expect(Order::query()->count())->toBe(1);

    HarborServer::actingAs($agent)->tool(SearchOrdersTool::class, [
        'status' => OrderStatus::Pending->value,
    ])->assertOk()->assertStructuredContentPath('total', 1);
});

test('an unqualified lead cannot be ordered', function () {
    $owner = harborTeam()['owner'];
    $listing = harborListing($owner, ['status' => ListingStatus::Published]);
    $lead = Lead::factory()->forListing($listing)->create([
        'status' => LeadStatus::New,
        'message' => 'Just looking, no commitment yet today.',
    ]);

    HarborServer::actingAs($owner)->tool(PlaceOrderTool::class, [
        'lead_id' => $lead->id,
        'confirm' => true,
    ])->assertHasErrors();

    expect(Order::query()->count())->toBe(0);
});
