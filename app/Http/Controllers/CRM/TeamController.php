<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request)
    {
        $team = Team::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $request->manager_id ?: null,
            'status' => $request->status ?? 'active',
        ]);
        $team->users()->sync($this->memberIds($request));

        return back()->with('success', 'Team created.');
    }

    public function update(StoreTeamRequest $request, Team $team)
    {
        $this->authorize('update', $team);
        $team->update([
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $request->manager_id ?: null,
            'status' => $request->status ?? $team->status,
        ]);
        $team->users()->sync($this->memberIds($request));

        return back()->with('success', 'Team updated.');
    }

    private function memberIds(StoreTeamRequest $request): array
    {
        $ids = collect($request->user_ids ?? [])->filter()->map(fn ($id) => (int) $id);
        if ($request->manager_id) {
            $ids->push((int) $request->manager_id);
        }

        return $ids->unique()->values()->all();
    }
}
