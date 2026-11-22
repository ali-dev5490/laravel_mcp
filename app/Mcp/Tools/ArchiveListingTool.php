<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Listings\ListingArchiver;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\WorkspaceActor;
use App\Mcp\Support\WorkspaceRecords;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('archive-listing')]
#[Title('Archive listing')]
#[Description('Archive a listing so it leaves the active catalog. Omitting confirm, or passing confirm=false, returns a preview including how many open leads would be left behind, and writes nothing. confirm=true archives it. Repeating the call is safe. Only workspace owners can see or call this tool.')]
#[IsDestructive]
#[IsIdempotent]
#[IsOpenWorld(false)]
class ArchiveListingTool extends Tool
{
    use InteractsWithWorkspace;

    public function shouldRegister(Request $request): bool
    {
        return app(WorkspaceActor::class)->resolve($request)?->isOwner() ?? false;
    }

    public function handle(Request $request, WorkspaceRecords $records, ListingArchiver $archiver): Response|ResponseFactory
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'listing' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
            'confirm' => ['nullable', 'boolean'],
        ], [
            'reason.required' => 'Give a short reason. It is included in the preview and the confirmation.',
            'reason.min' => 'The reason needs at least 3 characters.',
        ]);

        $listing = $records->listing($actor, $validated['listing']);
        $denied = $this->denied('archive', $listing);

        if ($denied instanceof Response) {
            return $denied;
        }

        $confirm = (bool) ($validated['confirm'] ?? false);
        $payload = $confirm
            ? $archiver->commit($actor, $listing, $validated['reason'])
            : $archiver->preview($listing, $validated['reason']);

        return Response::structured($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'listing' => $schema->string()->description('Listing id or slug.')->required()->max(120),
            'reason' => $schema->string()->description('Why the listing is being archived.')->required()->min(3)->max(200),
            'confirm' => $schema->boolean()
                ->description('False previews the archive and writes nothing. True archives the listing.')
                ->default(false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'committed' => $schema->boolean()->required(),
            'requires_confirmation' => $schema->boolean()->required(),
            'already_archived' => $schema->boolean()->required(),
            'message' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
            'open_leads' => $schema->integer()->description('Leads that are not lost or converted.')->required(),
            'listing' => $schema->object([])->required(),
        ];
    }
}
