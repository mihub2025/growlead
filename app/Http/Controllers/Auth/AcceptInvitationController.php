<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcceptInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();
        abort_unless($invitation->isValid(), 410, 'This invitation is no longer valid.');

        return view('auth.accept-invitation', compact('invitation'));
    }

    public function store(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();
        abort_unless($invitation->isValid(), 410);

        $request->validate(['password' => ['required', 'confirmed', 'min:8']]);

        $user = User::create([
            'organization_id' => $invitation->organization_id,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'password' => $request->password,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        if ($invitation->role_id) {
            $user->roles()->attach($invitation->role_id);
        }
        if ($invitation->team_id) {
            $user->teams()->attach($invitation->team_id);
        }
        $invitation->update(['accepted_at' => now()]);
        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
