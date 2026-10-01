<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'This account is not active.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('super.dashboard');
        }

        $user->loadMissing('organization');

        if (! $user->organization_id || ! $user->organization) {
            abort(403, 'This account is not attached to an organization.');
        }

        if ($user->organization->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This organization is suspended. Contact the platform administrator.',
            ]);
        }

        return $next($request);
    }
}
