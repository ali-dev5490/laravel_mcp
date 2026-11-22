<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;

final class WorkspaceActor
{
    public function resolve(Request $request): ?User
    {
        $authenticated = $request->user();

        if ($authenticated instanceof User) {
            return $authenticated;
        }

        if (! $this->allowsLocalFallback()) {
            return null;
        }

        $email = (string) config('harbor.local_user');
        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User) {
            Auth::setUser($user);
        }

        return $user;
    }

    public function require(Request $request): User
    {
        $user = $this->resolve($request);

        if (! $user instanceof User) {
            throw new AuthorizationException(
                'No Harborboard user is available. HTTP calls need a Sanctum bearer token. The stdio server uses HARBOR_LOCAL_USER, which must match a seeded member.'
            );
        }

        if ($user->workspace_id === null) {
            throw new AuthorizationException('This user is not a member of a workspace, so Harborboard has nothing to scope.');
        }

        Auth::setUser($user);

        return $user;
    }

    /**
     * Stdio has no HTTP session. Falling back to a configured user is safe
     * only for the local Artisan process, never for a web request or a test.
     */
    private function allowsLocalFallback(): bool
    {
        return app()->runningInConsole() && ! app()->runningUnitTests();
    }
}
