<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'This account is not active.');
        }

        if (! $user->isSuperAdmin()) {
            return redirect()->route('crm.dashboard');
        }

        return $next($request);
    }
}
