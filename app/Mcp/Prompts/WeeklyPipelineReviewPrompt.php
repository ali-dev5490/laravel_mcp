<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Domain\Pipeline\PipelineSummary;
use App\Mcp\Support\WorkspaceActor;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('weekly-pipeline-review')]
#[Title('Weekly pipeline review')]
#[Description('Turn the live pipeline summary into a weekly review: what needs a reply, what is qualified but unsold, and which orders are still open.')]
class WeeklyPipelineReviewPrompt extends Prompt
{
    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('focus', 'listings, leads, orders, or all. Defaults to all.', false),
        ];
    }

    /**
     * @return array<int, Response>
     */
    public function handle(Request $request, WorkspaceActor $actor, PipelineSummary $summary): array
    {
        $user = $actor->require($request);

        $validated = $request->validate([
            'focus' => ['nullable', Rule::in(['listings', 'leads', 'orders', 'all'])],
        ], [
            'focus.in' => 'Focus must be listings, leads, orders, or all.',
        ]);

        $focus = $validated['focus'] ?? 'all';
        $facts = json_encode(
            $summary->for($user),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return [
            Response::text(
                "Write a weekly pipeline review for {$user->workspace?->name}. Focus on {$focus}. Use only the snapshot in the next message. Lead with the items in attention. Separate facts from suggested actions. Do not place orders or change statuses in this prompt."
            )->asAssistant(),
            Response::text("Pipeline snapshot:\n\n".$facts),
        ];
    }
}
