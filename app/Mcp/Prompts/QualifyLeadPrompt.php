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

#[Name('qualify-lead')]
#[Title('Qualify a lead')]
#[Description('Build a briefing that decides whether a lead should move to qualified, stay where it is, or be marked lost. Uses the live lead record. Does not change data.')]
class QualifyLeadPrompt extends Prompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('lead_id', 'Numeric id of the lead to qualify.', true),
            new Argument('tone', 'direct, warm, or concise. Defaults to direct.', false),
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
            'tone' => ['nullable', Rule::in(['direct', 'warm', 'concise'])],
        ], [
            'lead_id.required' => 'Pass the numeric lead id, for example from search-leads.',
            'tone.in' => 'Tone must be direct, warm, or concise.',
        ]);

        $lead = $records->lead($user, (int) $validated['lead_id']);
        $tone = $validated['tone'] ?? 'direct';
        $facts = json_encode(
            $presenter->leadDetail($lead),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return [
            Response::text(
                "You qualify leads for {$user->workspace?->name}. Write in a {$tone} tone. Use only the facts in the next message and the qualification playbook. Do not invent budget, dates, or a different price. Recommend one next status and one next action. If the recommendation is an order, tell the person to preview it with place-order before confirming."
            )->asAssistant(),
            Response::text("Lead record:\n\n".$facts),
        ];
    }
}
