<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Domain\Pipeline\PipelineSummary;
use App\Mcp\Support\WorkspaceActor;
use App\Models\Listing;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('workspace-overview')]
#[Title('Workspace overview')]
#[Description('JSON snapshot of the signed-in workspace: who is calling, pipeline counts, and leads that need attention.')]
#[Uri('harbor://workspace/overview')]
#[MimeType('application/json')]
#[Audience(Role::Assistant)]
#[Priority(0.8)]
class WorkspaceOverviewResource extends Resource
{
    public function handle(Request $request, WorkspaceActor $actor, PipelineSummary $summary): Response
    {
        $user = $actor->require($request);

        if (Gate::denies('viewAny', Listing::class)) {
            return Response::error('You cannot read this workspace.');
        }

        return Response::text($this->encode($summary->for($user)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
