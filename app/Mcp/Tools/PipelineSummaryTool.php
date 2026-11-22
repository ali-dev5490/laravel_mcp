<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Pipeline\PipelineSummary;
use App\Mcp\Concerns\InteractsWithWorkspace;
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

#[Name('pipeline-summary')]
#[Title('Pipeline summary')]
#[Description('Summarize the signed-in workspace: listing, lead, and order counts, money by order status, and the leads that need attention. Read-only. Call this before a weekly review.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class PipelineSummaryTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, PipelineSummary $summary): Response|ResponseFactory
    {
        $actor = $this->actor($request);

        return Response::structured($summary->for($actor));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'workspace' => $schema->object([
                'id' => $schema->integer()->required(),
                'name' => $schema->string()->required(),
                'slug' => $schema->string()->required(),
            ])->required(),
            'actor' => $schema->object([
                'id' => $schema->integer()->required(),
                'name' => $schema->string()->required(),
                'email' => $schema->string()->required(),
                'role' => $schema->string()->required(),
            ])->required(),
            'listings' => $schema->object([])->description('Counts keyed by listing status.')->required(),
            'leads' => $schema->object([])->description('Counts keyed by lead status.')->required(),
            'orders' => $schema->object([])->description('Counts and decimal amounts keyed by order status.')->required(),
            'attention' => $schema->array()->description('New and qualified leads that still need a human.')->required(),
        ];
    }
}
