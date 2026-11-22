<?php

use App\Enums\LeadStatus;
use App\Mcp\Prompts\DraftFollowUpPrompt;
use App\Mcp\Prompts\QualifyLeadPrompt;
use App\Mcp\Prompts\WeeklyPipelineReviewPrompt;
use App\Mcp\Resources\LeadResource;
use App\Mcp\Resources\ListingResource;
use App\Mcp\Resources\QualificationPlaybookResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
use App\Mcp\Servers\HarborServer;
use App\Mcp\Tools\PipelineSummaryTool;
use App\Models\Lead;

test('resources return workspace data and the playbook', function () {
    $owner = harborTeam()['owner'];
    $listing = harborListing($owner, [
        'title' => 'Oak writing desk',
        'slug' => 'oak-writing-desk',
        'description' => 'Leather writing inset.',
    ]);
    $lead = Lead::factory()->forListing($listing)->create([
        'name' => 'Maya Ellis',
        'message' => 'Hold the desk through Friday please.',
        'status' => LeadStatus::Qualified,
    ]);

    HarborServer::actingAs($owner)->resource(WorkspaceOverviewResource::class)
        ->assertOk()
        ->assertSee('Northline Studio');

    HarborServer::actingAs($owner)->resource(QualificationPlaybookResource::class)
        ->assertOk()
        ->assertSee('confirm');

    HarborServer::actingAs($owner)->resource(ListingResource::class, [
        'listing' => 'oak-writing-desk',
    ])->assertOk()->assertSee('Leather writing inset.');

    HarborServer::actingAs($owner)->resource(LeadResource::class, [
        'lead' => (string) $lead->id,
    ])->assertOk()->assertSee('Maya Ellis');

    HarborServer::actingAs($owner)->resource(LeadResource::class, [
        'lead' => '9999',
    ])->assertHasErrors();
});

test('prompts embed live records and do not write', function () {
    $agent = harborTeam()['agent'];
    $listing = harborListing($agent, [
        'title' => 'Linen lounge chair',
        'price_cents' => 62_000,
    ]);
    $lead = Lead::factory()->forListing($listing)->create([
        'name' => 'Chris Patel',
        'message' => 'Can I see the chair on Thursday afternoon?',
        'status' => LeadStatus::New,
    ]);

    HarborServer::actingAs($agent)->prompt(QualifyLeadPrompt::class, [
        'lead_id' => $lead->id,
        'tone' => 'warm',
    ])->assertOk()->assertSee('Chris Patel')->assertSee('warm');

    HarborServer::actingAs($agent)->prompt(DraftFollowUpPrompt::class, [
        'lead_id' => $lead->id,
        'channel' => 'sms',
    ])->assertOk()->assertSee('sms')->assertSee('620.00');

    HarborServer::actingAs($agent)->prompt(WeeklyPipelineReviewPrompt::class, [
        'focus' => 'leads',
    ])->assertOk()->assertSee('Northline Studio')->assertSee('leads');

    HarborServer::actingAs($agent)->tool(PipelineSummaryTool::class)
        ->assertOk()
        ->assertStructuredContentPath('leads.new', 1)
        ->assertStructuredContentPath('workspace.slug', 'northline');

    expect($lead->refresh()->status)->toBe(LeadStatus::New);
});
