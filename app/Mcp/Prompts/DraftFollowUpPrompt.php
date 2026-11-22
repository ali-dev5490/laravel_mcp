<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Mcp\Support\RecordPresenter;
use App\Mcp\Support\WorkspaceActor;
use App\Mcp\Support\WorkspaceRecords;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('draft-follow-up')]
#[Title('Draft a follow-up')]
#[Description('Draft an email or SMS follow-up from the live lead and listing. The draft quotes the recorded price and does not send anything.')]
class DraftFollowUpPrompt extends Prompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('lead_id', 'Numeric id of the lead to reply to.', true),
            new Argument('channel', 'email or sms. Defaults to email.', false),
        ];
    }

    /**
     * @return array<int, Response>
     */
    public function handle(Request $request, WorkspaceActor $actor, WorkspaceRecords $records, RecordPresenter $presenter): array
    {
        $user = $actor->require($request);

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'min:1'],
            'channel' => ['nullable', Rule::in(['email', 'sms'])],
        ], [
            'channel.in' => 'Channel must be email or sms.',
        ]);

        $lead = $records->lead($user, (int) $validated['lead_id']);
        $lead->load('listing');
        $channel = $validated['channel'] ?? 'email';
        $facts = json_encode(
            $presenter->leadDetail($lead),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        $length = $channel === 'sms'
            ? 'Keep it under 320 characters.'
            : 'Include a subject line and a short body.';

        return [
            Response::text(
                "Draft a {$channel} follow-up as {$user->name} at {$user->workspace?->name}. {$length} Quote the listing price exactly as recorded. Do not invent availability, shipping, or a discount. Do not claim the message was sent."
            )->asAssistant(),
            Response::text("Lead record:\n\n".$facts),
        ];
    }
}
