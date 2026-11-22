<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpAbility
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->tokenCan('mcp:use') || $user->tokenCan('*')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'This token cannot use the Harborboard MCP server. Issue one with php artisan harbor:token {email}.',
        ], 403);
    }
}
