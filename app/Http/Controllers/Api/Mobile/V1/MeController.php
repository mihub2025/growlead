<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Requests\Mobile\ChangePasswordRequest;
use App\Http\Resources\Mobile\UserResource;
use App\Services\Mobile\AgentDashboardService;
use Illuminate\Http\Request;

class MeController extends MobileController
{
    public function show(Request $request, AgentDashboardService $dashboard)
    {
        $user = $request->user()->load(['roles', 'organization']);
        $stats = $dashboard->build($user)['stats'];

        return $this->ok([
            'user' => new UserResource($user),
            'stats' => $stats,
        ]);
    }

    public function password(ChangePasswordRequest $request)
    {
        $request->user()->update(['password' => $request->password]);

        return $this->ok(null, 'Password updated.');
    }
}
