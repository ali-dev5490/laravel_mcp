<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Leads\LeadRegistrar;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
use App\Mcp\Support\WorkspaceActor;
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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('create-lead')]
#[Title('Create lead')]
#[Description('Create an inquiry in the signed-in workspace. This writes immediately after validation. Pass idempotency_key to make a retry return the original lead instead of a duplicate. Viewers cannot call this tool.')]
#[IsOpenWorld(false)]
class CreateLeadTool extends Tool
{
    use InteractsWithWorkspace;

    public function shouldRegister(Request $request): bool
    {
        return app(WorkspaceActor::class)->resolve($request)?->canWrite() ?? false;
    }

    public function handle(Request $request, WorkspaceRecords $records, LeadRegistrar $registrar, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);
        $denied = $this->denied('create', Lead::class);

        if ($denied instanceof Response) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'listing' => ['nullable', 'string', 'max:120'],
            'assignee_email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:40'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ], [
            'name.required' => 'Provide the buyer\'s name.',
            'email.required' => 'Provide an email so the workspace can reply.',
            'message.min' => 'The message needs at least 10 characters so the team knows what the buyer wants.',
            'listing.max' => 'Pass a listing id or slug, or omit listing for a general inquiry.',
        ]);

        $listingId = null;
        if (isset($validated['listing']) && $validated['listing'] !== '') {
            $listingId = $records->listing($actor, (string) $validated['listing'])->id;
        }

        $assigneeId = null;
        if (isset($validated['assignee_email']) && $validated['assignee_email'] !== '') {
            $assigneeId = $records->memberByEmail($actor, (string) $validated['assignee_email'])->id;
        }

        $result = $registrar->create($actor, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'message' => $validated['message'],
            'source' => $validated['source'] ?? 'mcp',
            'idempotency_key' => $validated['idempotency_key'] ?? null,
            'assignee_id' => $assigneeId,
            'listing_id' => $listingId,
        ]);

        $lead = $result['lead']->load(['listing', 'assignee']);

        return Response::structured([
            'replayed' => $result['replayed'],
            'message' => $result['replayed']
                ? 'An existing lead already used this idempotency key. Nothing new was written.'
                : 'Lead created with status new.',
            'lead' => $presenter->leadSummary($lead),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Buyer name.')->required()->max(120),
            'email' => $schema->string()->description('Buyer email.')->required()->format('email'),
            'phone' => $schema->string()->description('Optional phone number.')->max(40),
            'company' => $schema->string()->description('Optional company.')->max(120),
            'message' => $schema->string()->description('What the buyer wants, at least 10 characters.')->required()->min(10)->max(2000),
            'listing' => $schema->string()->description('Optional listing id or slug this inquiry is about.')->max(120),
            'assignee_email' => $schema->string()->description('Optional teammate email in this workspace.')->format('email'),
            'source' => $schema->string()->description('Where the inquiry came from. Defaults to mcp.')->max(40),
            'idempotency_key' => $schema->string()->description('Optional client key. Repeating it returns the original lead.')->max(80),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'replayed' => $schema->boolean()->description('True when the idempotency key matched an existing lead.')->required(),
            'message' => $schema->string()->required(),
            'lead' => $schema->object([])->required(),
        ];
    }
}
