<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\LeadStatus;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
use App\Mcp\Support\WorkspaceRecords;
use App\Models\Lead;
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

#[Name('search-leads')]
#[Title('Search leads')]
#[Description('Search inquiries in the signed-in workspace by text, status, listing, or assignee email. Read-only and scoped to that workspace.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class SearchLeadsTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, WorkspaceRecords $records, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);
        $denied = $this->denied('viewAny', Lead::class);

        if ($denied instanceof Response) {
            return $denied;
        }

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:'.implode(',', array_column(LeadStatus::cases(), 'value'))],
            'listing' => ['nullable', 'string', 'max:120'],
            'assignee_email' => ['nullable', 'email', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ], [
            'status.in' => 'Status must be new, contacted, qualified, lost, or converted.',
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $offset = (int) ($validated['offset'] ?? 0);

        $leads = Lead::query()->with(['listing', 'assignee'])->where('workspace_id', $actor->workspace_id);

        if (isset($validated['query']) && trim((string) $validated['query']) !== '') {
            $term = '%'.trim(str_replace(['%', '_'], '', (string) $validated['query'])).'%';
            $leads->where(function ($query) use ($term): void {
                $query->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('message', 'like', $term);
            });
        }

        if (isset($validated['status'])) {
            $leads->where('status', $validated['status']);
        }

        if (isset($validated['listing'])) {
            $listing = $records->listing($actor, (string) $validated['listing']);
            $leads->where('listing_id', $listing->id);
        }

        if (isset($validated['assignee_email'])) {
            $assignee = $records->memberByEmail($actor, (string) $validated['assignee_email']);
            $leads->where('assignee_id', $assignee->id);
        }

        $total = (clone $leads)->count();
        $rows = $leads->orderByDesc('id')->skip($offset)->take($limit)->get();

        return Response::structured([
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'leads' => $rows->map(fn (Lead $lead): array => $presenter->leadSummary($lead))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Matched against name, email, company, and message.')->max(120),
            'status' => $schema->string()->enum(LeadStatus::class)->description('Filter by lead status.'),
            'listing' => $schema->string()->description('Restrict to one listing id or slug.')->max(120),
            'assignee_email' => $schema->string()->description('Restrict to a teammate in this workspace.')->format('email'),
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
            'leads' => $schema->array()->required(),
        ];
    }
}
