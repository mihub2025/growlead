<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Resources\Mobile\LeadResource;
use App\Http\Resources\Mobile\TaskResource;
use App\Http\Resources\Mobile\UserResource;
use App\Services\Mobile\AgentDashboardService;
use Illuminate\Http\Request;

class DashboardController extends MobileController
{
    public function show(Request $request, AgentDashboardService $dashboard)
    {
        $payload = $dashboard->build($request->user()->load(['roles', 'organization']));

        return $this->ok([
            'greeting' => $payload['greeting'],
            'agent' => new UserResource($request->user()),
            'metrics' => $payload['metrics'],
            'today_tasks' => TaskResource::collection($payload['today_tasks']),
            'needs_attention' => LeadResource::collection($payload['needs_attention']),
            'stats' => $payload['stats'],
            'pipeline' => $payload['pipeline'],
            'productivity' => $payload['productivity'],
            'campaigns' => $payload['campaigns'],
        ]);
    }
}
