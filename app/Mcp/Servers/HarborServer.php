<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DraftFollowUpPrompt;
use App\Mcp\Prompts\QualifyLeadPrompt;
use App\Mcp\Prompts\WeeklyPipelineReviewPrompt;
use App\Mcp\Resources\LeadResource;
use App\Mcp\Resources\ListingResource;
use App\Mcp\Resources\QualificationPlaybookResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
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
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Harborboard')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
You are connected to Harborboard, a classifieds CRM for one workspace.
Every read and write is scoped to the signed-in member. If a record is missing, say so. Do not infer that it exists in another workspace.
Read tools and resources are safe. create-lead and update-lead-status write as soon as validation passes.
place-order and archive-listing never write unless confirm is true. Call them first with confirm omitted or false, show the preview to the person you are helping, and only then call again with confirm=true.
Never invent prices, statuses, buyers, or order numbers. Prices are decimal strings in the listing currency.
The converted lead status is produced only by a confirmed order.
Read harbor://playbook/qualification before you recommend that a lead is qualified.
TEXT)]
class HarborServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
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
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        WorkspaceOverviewResource::class,
        QualificationPlaybookResource::class,
        ListingResource::class,
        LeadResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        QualifyLeadPrompt::class,
        DraftFollowUpPrompt::class,
        WeeklyPipelineReviewPrompt::class,
    ];
}
