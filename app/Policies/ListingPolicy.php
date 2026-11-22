<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null;
    }

    public function view(User $user, Listing $listing): bool
    {
        return $user->workspace_id === $listing->workspace_id;
    }

    public function archive(User $user, Listing $listing): Response|bool
    {
        if ($user->workspace_id !== $listing->workspace_id) {
            return Response::deny('That listing is not in your workspace.');
        }

        if (! $user->isOwner()) {
            return Response::deny('Only a workspace owner can archive listings.');
        }

        return true;
    }
}
