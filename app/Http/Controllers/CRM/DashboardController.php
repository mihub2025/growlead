<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard)
    {
        $this->authorize('dashboard.view');
        $org = $request->user()->organization;
        $data = $dashboard->build($org, $request->only(['from', 'to', 'source_id', 'campaign_id', 'team_id', 'user_id']));

        return view('crm.dashboard.index', [
            'data' => $data,
            'sources' => $org->leadSources ?? \App\Models\LeadSource::forOrganization($org->id)->get(),
            'campaigns' => $org->campaigns()->orderBy('name')->get(),
            'teams' => $org->teams()->orderBy('name')->get(),
            'users' => $org->users()->orderBy('name')->get(),
            'filters' => $request->all(),
        ]);
    }
}
