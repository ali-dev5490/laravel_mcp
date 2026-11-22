<?php

declare(strict_types=1);

namespace App\Domain\Leads;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeadStatusUpdater
{
    public function update(User $actor, Lead $lead, LeadStatus $to, ?string $note): Lead
    {
        if ($lead->status === $to) {
            $this->recordNote($actor, $lead, $note);

            return $lead->refresh();
        }

        if (! LeadStatusTransition::allows($lead->status, $to)) {
            throw ValidationException::withMessages([
                'status' => LeadStatusTransition::message($lead->status, $to),
            ]);
        }

        return DB::transaction(function () use ($actor, $lead, $to, $note): Lead {
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if ($from !== $to && ! LeadStatusTransition::allows($from, $to)) {
                throw ValidationException::withMessages([
                    'status' => LeadStatusTransition::message($from, $to),
                ]);
            }

            if ($from !== $to) {
                $locked->status = $to;
                $locked->save();

                $locked->activities()->create([
                    'user_id' => $actor->id,
                    'type' => LeadActivityType::StatusChange,
                    'from_status' => $from,
                    'to_status' => $to,
                    'body' => $note,
                ]);
            }

            return $locked->refresh();
        });
    }

    private function recordNote(User $actor, Lead $lead, ?string $note): void
    {
        if ($note === null || $note === '') {
            return;
        }

        $latest = $lead->activities()->latest('id')->first();

        if (
            $latest instanceof LeadActivity
            && $latest->type === LeadActivityType::Note
            && $latest->body === $note
        ) {
            return;
        }

        $lead->activities()->create([
            'user_id' => $actor->id,
            'type' => LeadActivityType::Note,
            'body' => $note,
        ]);
    }
}
