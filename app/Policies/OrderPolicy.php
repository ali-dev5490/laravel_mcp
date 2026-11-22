<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null;
    }

    public function create(User $user): Response|bool
    {
        if ($user->workspace_id === null) {
            return Response::deny('You are not a member of a workspace.');
        }

        if (! $user->canWrite()) {
            return Response::deny('Your role can read orders but cannot place them.');
        }

        return true;
    }
}
