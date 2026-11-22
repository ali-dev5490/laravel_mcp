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

#[Name('get-lead')]
#[Title('Get lead')]
#[Description('Read one lead in the signed-in workspace, including the original message and the activity history. Read-only.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetLeadTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, WorkspaceRecords $records, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'min:1'],
        ], [
            'lead_id.required' => 'Pass the numeric lead id from search-leads.',
        ]);

        $lead = $records->lead($actor, (int) $validated['lead_id']);

        return Response::structured($presenter->leadDetail($lead));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->integer()->description('Numeric id of the lead.')->required()->min(1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'name' => $schema->string()->required(),
            'email' => $schema->string()->required(),
            'status' => $schema->string()->required(),
            'message' => $schema->string()->required(),
            'activities' => $schema->array()->required(),
        ];
    }
}
