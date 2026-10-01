<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isActive()) {
            abort(403, 'This account is not active.');
        }

        if (! $user->organization_id) {
            abort(403, 'This account is not attached to an organization.');
        }

        $user->forceFill(['last_active_at' => now()])->saveQuietly();

        return $next($request);
    }
}
