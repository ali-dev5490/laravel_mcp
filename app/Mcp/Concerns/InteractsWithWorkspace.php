<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Mcp\Support\WorkspaceActor;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait InteractsWithWorkspace
{
    protected function actor(Request $request): User
    {
        return app(WorkspaceActor::class)->require($request);
    }

    protected function denied(string $ability, mixed $arguments): ?Response
    {
        $decision = Gate::inspect($ability, $arguments);

        if ($decision->denied()) {
            return Response::error($decision->message() ?? 'Permission denied.');
        }

        return null;
    }
}
