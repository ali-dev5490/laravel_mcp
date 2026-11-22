<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\OrderStatus;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
use App\Models\Order;
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

#[Name('search-orders')]
#[Title('Search orders')]
#[Description('List orders in the signed-in workspace. Optional status filter. Amounts are decimal strings. Read-only.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class SearchOrdersTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);
        $denied = $this->denied('viewAny', Order::class);

        if ($denied instanceof Response) {
            return $denied;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ], [
            'status.in' => 'Status must be pending, paid, fulfilled, or cancelled.',
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $offset = (int) ($validated['offset'] ?? 0);

        $orders = Order::query()->with(['listing', 'lead'])->where('workspace_id', $actor->workspace_id);

        if (isset($validated['status'])) {
            $orders->where('status', $validated['status']);
        }

        $total = (clone $orders)->count();
        $rows = $orders->orderByDesc('id')->skip($offset)->take($limit)->get();

        return Response::structured([
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'orders' => $rows->map(fn (Order $order): array => $presenter->order($order))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(OrderStatus::class)->description('Filter by order status.'),
            'limit' => $schema->integer()->description('Page size. Defaults to 10, maximum 25.')->min(1)->max(25),
            'offset' => $schema->integer()->description('Number of matches to skip.')->min(0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->required(),
            'limit' => $schema->integer()->required(),
            'offset' => $schema->integer()->required(),
            'orders' => $schema->array()->required(),
        ];
    }
}
