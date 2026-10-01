<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Resources\Mobile\LeadDetailResource;
use App\Models\Campaign;
use App\Services\Mobile\AgentCampaignService;
use Illuminate\Http\Request;

class CampaignController extends MobileController
{
    public function __construct(
        protected AgentCampaignService $campaigns
    ) {
    }

    public function index(Request $request)
    {
        return $this->ok($this->campaigns->list($request->user()));
    }

    public function nextCall(Request $request, Campaign $campaign)
    {
        $result = $this->campaigns->nextCall(
            $request->user(),
            $campaign,
            $request->integer('after_id') ?: null
        );

        return $this->ok([
            'campaign' => $result['campaign'],
            'lead' => $result['lead'] ? new LeadDetailResource($result['lead']) : null,
            'remaining' => $result['remaining'],
        ]);
    }
}
