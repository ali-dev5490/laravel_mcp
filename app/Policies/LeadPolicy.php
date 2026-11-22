<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->workspace_id === $lead->workspace_id;
    }

    public function create(User $user): Response|bool
    {
        if ($user->workspace_id === null) {
            return Response::deny('You are not a member of a workspace.');
        }

        if (! $user->canWrite()) {
            return Response::deny('Your role can read leads but cannot create them. An owner needs to change the role from viewer to agent.');
        }

        return true;
    }

    public function update(User $user, Lead $lead): Response|bool
    {
        if ($user->workspace_id !== $lead->workspace_id) {
            return Response::deny('That lead is not in your workspace.');
        }

        if (! $user->canWrite()) {
            return Response::deny('Your role can read leads but cannot change them.');
        }

        return true;
    }
}
