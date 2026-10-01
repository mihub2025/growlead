<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\PlatformOrganizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request, PlatformOrganizationService $platform)
    {
        $organizations = $platform->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null
        );

        return view('super.organizations.index', [
            'organizations' => $organizations,
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function create()
    {
        return view('super.organizations.create');
    }

    public function store(Request $request, PlatformOrganizationService $platform)
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:190'],
            'industry' => ['required', 'string', 'max:40', Rule::in(array_keys(config('crm.industries')))],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:8', Rule::in(array_keys(config('crm.currencies', ['USD' => 'USD'])))],
            'timezone' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $admin = $platform->create($data, $request->user());

        return redirect()
            ->route('super.organizations.show', $admin->organization)
            ->with('success', 'Organization created. The workspace admin can sign in now.');
    }

    public function show(Organization $organization, PlatformOrganizationService $platform)
    {
        return view('super.organizations.show', $platform->snapshot($organization));
    }

    public function updateStatus(Request $request, Organization $organization, PlatformOrganizationService $platform)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $platform->setStatus($organization, $data['status']);

        $label = $data['status'] === 'active' ? 'activated' : 'suspended';

        return back()->with('success', 'Organization '.$label.'.');
    }
}
