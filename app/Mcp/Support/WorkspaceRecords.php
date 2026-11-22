<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class WorkspaceRecords
{
    public function listing(User $actor, string $key): Listing
    {
        $query = Listing::query()->where('workspace_id', $actor->workspace_id);

        $listing = null;

        if (ctype_digit($key)) {
            $listing = (clone $query)->whereKey((int) $key)->first();
        }

        if (! $listing instanceof Listing) {
            $listing = (clone $query)->where('slug', $key)->first();
        }

        if (! $listing instanceof Listing) {
            throw ValidationException::withMessages([
                'listing' => "No listing in your workspace matches [{$key}]. Pass a numeric id or a slug such as oak-writing-desk.",
            ]);
        }

        return $listing;
    }

    public function lead(User $actor, int $id): Lead
    {
        $lead = Lead::query()
            ->where('workspace_id', $actor->workspace_id)
            ->whereKey($id)
            ->first();

        if (! $lead instanceof Lead) {
            throw ValidationException::withMessages([
                'lead_id' => "No lead with id {$id} exists in your workspace.",
            ]);
        }

        return $lead;
    }

    public function memberByEmail(User $actor, string $email): User
    {
        $member = User::query()
            ->where('workspace_id', $actor->workspace_id)
            ->where('email', $email)
            ->first();

        if (! $member instanceof User) {
            throw ValidationException::withMessages([
                'assignee_email' => "No teammate in your workspace uses [{$email}].",
            ]);
        }

        return $member;
    }
}
