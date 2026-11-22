<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Orders\OrderPlacement;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\WorkspaceActor;
use App\Mcp\Support\WorkspaceRecords;
use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('place-order')]
#[Title('Place order')]
#[Description('Turn a qualified lead into an order at the listing price and mark the listing sold. Omitting confirm, or passing confirm=false, returns a preview and writes nothing. Pass confirm=true only after a person has approved the preview. Viewers cannot call this tool.')]
#[IsOpenWorld(false)]
class PlaceOrderTool extends Tool
{
    use InteractsWithWorkspace;

    public function shouldRegister(Request $request): bool
    {
        return app(WorkspaceActor::class)->resolve($request)?->canWrite() ?? false;
    }

    public function handle(Request $request, WorkspaceRecords $records, OrderPlacement $placement): Response|ResponseFactory
    {
        $actor = $this->actor($request);
        $denied = $this->denied('create', Order::class);

        if ($denied instanceof Response) {
            return $denied;
        }

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'confirm' => ['nullable', 'boolean'],
        ], [
            'lead_id.required' => 'Pass the id of a qualified lead. Call this first with confirm=false to preview.',
        ]);

        $lead = $records->lead($actor, (int) $validated['lead_id']);
        $lead->load('listing');
        $notes = $validated['notes'] ?? null;
        $confirm = (bool) ($validated['confirm'] ?? false);

        $payload = $confirm
            ? $placement->commit($actor, $lead, $notes)
            : $placement->preview($actor, $lead, $notes);

        return Response::structured($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->integer()->description('Qualified lead to convert.')->required()->min(1),
            'notes' => $schema->string()->description('Optional note stored on the order.')->max(500),
            'confirm' => $schema->boolean()
                ->description('False returns a preview and writes nothing. True commits the order, converts the lead, and marks the listing sold.')
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
            'message' => $schema->string()->required(),
            'preview' => $schema->object([])->nullable(),
            'order' => $schema->object([])->nullable(),
        ];
    }
}
