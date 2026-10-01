<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAutomationRequest;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AutomationRule::class);
        $rules = AutomationRule::forOrganization($request->user()->organization_id)->latest()->paginate(20);
        $runs = AutomationRun::forOrganization($request->user()->organization_id)->with('rule')->latest()->limit(20)->get();

        return view('crm.automations.index', compact('rules', 'runs'));
    }

    public function store(StoreAutomationRequest $request)
    {
        AutomationRule::create($request->validated() + [
            'organization_id' => $request->user()->organization_id,
            'created_by' => $request->user()->id,
            'status' => $request->status ?? 'active',
        ]);

        return back()->with('success', 'Automation created.');
    }

    public function update(StoreAutomationRequest $request, AutomationRule $automation)
    {
        $this->authorize('update', $automation);
        $automation->update($request->validated());

        return back()->with('success', 'Automation updated.');
    }

    public function destroy(AutomationRule $automation)
    {
        $this->authorize('update', $automation);
        $automation->delete();

        return back()->with('success', 'Automation deleted.');
    }
}
