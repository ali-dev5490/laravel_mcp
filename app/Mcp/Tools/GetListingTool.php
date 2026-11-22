<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
use App\Mcp\Support\WorkspaceRecords;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-listing')]
#[Title('Get listing')]
#[Description('Read one listing in the signed-in workspace by numeric id or slug, including the description and lead counts by status. Read-only.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetListingTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, WorkspaceRecords $records, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'listing' => ['required', 'string', 'max:120'],
        ], [
            'listing.required' => 'Pass a listing id or slug, for example oak-writing-desk.',
        ]);

        $listing = $records->listing($actor, $validated['listing']);

        return Response::structured($presenter->listingDetail($listing));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'listing' => $schema->string()
                ->description('Numeric id or slug of the listing.')
                ->required()
                ->max(120),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'slug' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'status' => $schema->string()->required(),
            'category' => $schema->string()->required(),
            'price' => $schema->string()->required(),
            'currency' => $schema->string()->required(),
            'location' => $schema->string()->nullable(),
            'summary' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'published_at' => $schema->string()->nullable(),
            'lead_counts' => $schema->object([])->description('Lead totals keyed by status.')->required(),
        ];
    }
}
