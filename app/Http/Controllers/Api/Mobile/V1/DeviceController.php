<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Requests\Mobile\RegisterDeviceRequest;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceController extends MobileController
{
    public function store(RegisterDeviceRequest $request)
    {
        $user = $request->user();
        $token = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $request->token,
            ],
            [
                'organization_id' => $user->organization_id,
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'last_used_at' => now(),
            ]
        );

        return $this->ok(['id' => $token->id], 'Device registered.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);
        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $request->token)
            ->delete();

        return $this->ok(null, 'Device removed.');
    }
}
