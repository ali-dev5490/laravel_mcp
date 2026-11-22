<?php

use App\Mcp\Prompts\DraftFollowUpPrompt;
use App\Mcp\Prompts\QualifyLeadPrompt;
use App\Mcp\Prompts\WeeklyPipelineReviewPrompt;
use App\Mcp\Resources\QualificationPlaybookResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
use App\Mcp\Servers\HarborServer;
use App\Mcp\Tools\ArchiveListingTool;
use App\Mcp\Tools\CreateLeadTool;
use App\Mcp\Tools\GetLeadTool;
use App\Mcp\Tools\GetListingTool;
use App\Mcp\Tools\PipelineSummaryTool;
use App\Mcp\Tools\PlaceOrderTool;
use App\Mcp\Tools\SearchLeadsTool;
use App\Mcp\Tools\SearchListingsTool;
use App\Mcp\Tools\SearchOrdersTool;
use App\Mcp\Tools\UpdateLeadStatusTool;

test('an owner sees every tool, resource, and prompt', function () {
    $owner = harborTeam()['owner'];

    HarborServer::actingAs($owner)->tools()
        ->assertRegistered([
            SearchListingsTool::class,
            GetListingTool::class,
            SearchLeadsTool::class,
            GetLeadTool::class,
            PipelineSummaryTool::class,
            SearchOrdersTool::class,
            CreateLeadTool::class,
            UpdateLeadStatusTool::class,
            PlaceOrderTool::class,
            ArchiveListingTool::class,
        ]);

    HarborServer::actingAs($owner)->resources()
        ->assertRegistered([
            WorkspaceOverviewResource::class,
            QualificationPlaybookResource::class,
        ]);

    HarborServer::actingAs($owner)->prompts()
        ->assertRegistered([
            QualifyLeadPrompt::class,
            DraftFollowUpPrompt::class,
            WeeklyPipelineReviewPrompt::class,
        ]);
});

test('agents can write but cannot archive', function () {
    $agent = harborTeam()['agent'];

    HarborServer::actingAs($agent)->tools()
        ->assertRegistered([CreateLeadTool::class, PlaceOrderTool::class])
        ->assertNotRegistered([ArchiveListingTool::class]);
});

test('viewers only receive read tools', function () {
    $viewer = harborTeam()['viewer'];

    HarborServer::actingAs($viewer)->tools()
        ->assertRegistered([SearchListingsTool::class, PipelineSummaryTool::class])
        ->assertNotRegistered([
            CreateLeadTool::class,
            UpdateLeadStatusTool::class,
            PlaceOrderTool::class,
            ArchiveListingTool::class,
        ]);
});
