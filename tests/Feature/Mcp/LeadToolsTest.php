<?php

use App\Enums\LeadStatus;
use App\Mcp\Servers\HarborServer;
use App\Mcp\Tools\CreateLeadTool;
use App\Mcp\Tools\GetLeadTool;
use App\Mcp\Tools\SearchLeadsTool;
use App\Mcp\Tools\UpdateLeadStatusTool;
use App\Models\Lead;
use App\Models\User;

test('create lead validates, writes, and replays an idempotency key', function () {
    $agent = harborTeam()['agent'];
    $listing = harborListing($agent, ['slug' => 'oak-writing-desk']);

    HarborServer::actingAs($agent)->tool(CreateLeadTool::class, [
        'name' => 'Ada',
    ])->assertHasErrors();

    expect(Lead::query()->count())->toBe(0);

    $created = HarborServer::actingAs($agent)->tool(CreateLeadTool::class, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'message' => 'I would like the oak desk for a studio install.',
        'listing' => $listing->slug,
        'assignee_email' => 'agent@harborboard.test',
        'idempotency_key' => 'ada-desk-1',
    ]);

    $created->assertOk()
        ->assertStructuredContentPath('replayed', false)
        ->assertStructuredContentPath('lead.status', 'new')
        ->assertStructuredContentPath('lead.listing', $listing->title);

    $replayed = HarborServer::actingAs($agent)->tool(CreateLeadTool::class, [
        'name' => 'Someone Else',
        'email' => 'else@example.com',
        'message' => 'This payload must be ignored on replay.',
        'idempotency_key' => 'ada-desk-1',
    ]);

    $replayed->assertOk()->assertStructuredContentPath('replayed', true);

    expect(Lead::query()->count())->toBe(1)
        ->and(Lead::query()->first()->email)->toBe('ada@example.com');
});

test('viewers cannot create leads and search does not cross workspaces', function () {
    $team = harborTeam();
    $listing = harborListing($team['owner'], ['slug' => 'linen-lounge-chair', 'title' => 'Linen lounge chair']);

    Lead::factory()->forListing($listing)->create([
        'name' => 'Chris Patel',
        'email' => 'chris.patel@example.com',
        'message' => 'Is the chair still on the floor this week?',
        'status' => LeadStatus::New,
    ]);

    HarborServer::actingAs($team['viewer'])->tool(CreateLeadTool::class, [
        'name' => 'Sam',
        'email' => 'sam@example.com',
        'message' => 'Viewers should not be able to write this.',
    ])->assertHasErrors();

    HarborServer::actingAs($team['viewer'])->tool(SearchLeadsTool::class, [
        'query' => 'Chris',
    ])->assertOk()->assertStructuredContentPath('total', 1);

    $outsider = User::factory()->owner()->create();

    HarborServer::actingAs($outsider)->tool(SearchLeadsTool::class, [
        'query' => 'Chris',
    ])->assertStructuredContentPath('total', 0);
});

test('status changes follow the state machine', function () {
    $agent = harborTeam()['agent'];
    $listing = harborListing($agent);
    $lead = Lead::factory()->forListing($listing)->create([
        'status' => LeadStatus::New,
        'message' => 'Please tell me if this piece is still available.',
    ]);

    HarborServer::actingAs($agent)->tool(UpdateLeadStatusTool::class, [
        'lead_id' => $lead->id,
        'status' => 'qualified',
        'note' => 'Buyer confirmed the recorded price.',
    ])->assertOk()
        ->assertStructuredContentPath('changed', true)
        ->assertStructuredContentPath('to', 'qualified');

    HarborServer::actingAs($agent)->tool(UpdateLeadStatusTool::class, [
        'lead_id' => $lead->id,
        'status' => 'qualified',
    ])->assertStructuredContentPath('changed', false);

    HarborServer::actingAs($agent)->tool(UpdateLeadStatusTool::class, [
        'lead_id' => $lead->id,
        'status' => 'converted',
    ])->assertHasErrors();

    expect($lead->refresh()->status)->toBe(LeadStatus::Qualified);

    HarborServer::actingAs($agent)->tool(GetLeadTool::class, [
        'lead_id' => $lead->id,
    ])->assertOk()->assertSee('Buyer confirmed the recorded price.');
});
