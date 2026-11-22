<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Leads\LeadStatusUpdater;
use App\Enums\LeadStatus;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('update-lead-status')]
#[Title('Update lead status')]
#[Description('Move a lead along new, contacted, qualified, and lost. The same status is a no-op. Converted is reserved for place-order. This writes immediately. Viewers cannot call this tool.')]
#[IsIdempotent]
#[IsOpenWorld(false)]
class UpdateLeadStatusTool extends Tool
{
    use InteractsWithWorkspace;

    public function shouldRegister(Request $request): bool
    {
        return app(WorkspaceActor::class)->resolve($request)?->canWrite() ?? false;
    }

    public function handle(Request $request, WorkspaceRecords $records, LeadStatusUpdater $updater, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', array_column(LeadStatus::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Pass the next status: new, contacted, qualified, or lost. Do not pass converted.',
            'status.in' => 'Status must be new, contacted, qualified, lost, or converted. Converted is rejected on purpose.',
        ]);

        $lead = $records->lead($actor, (int) $validated['lead_id']);
        $denied = $this->denied('update', $lead);

        if ($denied instanceof Response) {
            return $denied;
        }

        $status = LeadStatus::from($validated['status']);
        $previous = $lead->status;
        $updated = $updater->update($actor, $lead, $status, $validated['note'] ?? null);
        $updated->load(['listing', 'assignee']);

        return Response::structured([
            'changed' => $previous !== $updated->status,
            'from' => $previous->value,
            'to' => $updated->status->value,
            'lead' => $presenter->leadSummary($updated),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->integer()->description('Lead to update.')->required()->min(1),
            'status' => $schema->string()->enum(LeadStatus::class)->description('Target status. converted is rejected; use place-order.')->required(),
            'note' => $schema->string()->description('Optional note stored on the activity.')->max(500),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'changed' => $schema->boolean()->required(),
            'from' => $schema->string()->required(),
            'to' => $schema->string()->required(),
            'lead' => $schema->object([])->required(),
        ];
    }
}
