<?php

declare(strict_types=1);

namespace App\Domain\Leads;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LeadRegistrar
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     company: string|null,
     *     message: string,
     *     source: string,
     *     idempotency_key: string|null,
     *     assignee_id: int|null,
     *     listing_id: int|null
     * }  $attributes
     * @return array{replayed: bool, lead: Lead}
     */
    public function create(User $actor, array $attributes): array
    {
        if ($attributes['idempotency_key'] !== null) {
            $existing = Lead::query()
                ->where('workspace_id', $actor->workspace_id)
                ->where('idempotency_key', $attributes['idempotency_key'])
                ->first();

            if ($existing instanceof Lead) {
                return ['replayed' => true, 'lead' => $existing];
            }
        }

        $lead = DB::transaction(function () use ($actor, $attributes): Lead {
            $lead = Lead::query()->create([
                'workspace_id' => $actor->workspace_id,
                'listing_id' => $attributes['listing_id'],
                'assignee_id' => $attributes['assignee_id'],
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'],
                'company' => $attributes['company'],
                'message' => $attributes['message'],
                'status' => LeadStatus::New,
                'source' => $attributes['source'],
                'idempotency_key' => $attributes['idempotency_key'],
            ]);

            $lead->activities()->create([
                'user_id' => $actor->id,
                'type' => LeadActivityType::Created,
                'to_status' => LeadStatus::New,
                'body' => 'Lead captured.',
            ]);

            return $lead;
        });

        return ['replayed' => false, 'lead' => $lead];
    }
}
