<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Requests\Mobile\LoginRequest;
use App\Http\Resources\Mobile\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends MobileController
{
    public function login(LoginRequest $request)
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Super administrators must sign in on the web console.'],
            ]);
        }

        $user->loadMissing('organization');
        if (! $user->organization_id || ! $user->organization || $user->organization->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This organization is not active.'],
            ]);
        }

        $user->tokens()->where('name', config('mobile.token_name'))->delete();
        $token = $user->createToken(config('mobile.token_name'))->plainTextToken;
        $user->forceFill(['last_active_at' => now()])->saveQuietly();
        $user->load(['roles', 'organization']);

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Signed in.');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->where('name', config('mobile.token_name'))->delete();
        }

        return $this->ok(null, 'Signed out.');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return $this->ok(null, 'If that email exists, a reset link has been sent.');
    }
}
