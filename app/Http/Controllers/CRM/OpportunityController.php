<?php

namespace App\Http\Controllers\CRM;

use App\Events\OpportunityWon;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('opportunities.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'lead_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer'],
            'estimated_value' => ['nullable', 'numeric'],
            'probability' => ['nullable', 'integer'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        Opportunity::create($data + [
            'organization_id' => $request->user()->organization_id,
            'currency' => $request->user()->organization->currency,
            'status' => 'open',
        ]);

        return back()->with('success', 'Opportunity created.');
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        abort_unless($opportunity->organization_id === $request->user()->organization_id, 403);
        $opportunity->update($request->validate([
            'status' => ['required', 'in:open,won,lost'],
            'lost_reason' => ['nullable', 'string'],
            'probability' => ['nullable', 'integer'],
        ]));
        if ($opportunity->status === 'won') {
            $opportunity->update(['closed_at' => now()]);
            OpportunityWon::dispatch($opportunity);
        }

        return back()->with('success', 'Opportunity updated.');
    }
}
