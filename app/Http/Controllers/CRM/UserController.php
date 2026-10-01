<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\GenericCrmNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $org = $request->user()->organization;
        $tab = $request->get('tab', 'active');
        $users = User::forOrganization($org->id)->with(['roles', 'teams'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($i) use ($s) {
                $i->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($tab !== 'teams', fn ($q) => $q->where('status', $tab === 'invited' ? 'invited' : $tab))
            ->paginate(10);

        $stats = [
            'total' => User::forOrganization($org->id)->count(),
            'agents' => User::forOrganization($org->id)->whereHas('roles', fn ($q) => $q->where('slug', 'agent'))->where('status', 'active')->count(),
            'managers' => User::forOrganization($org->id)->whereHas('roles', fn ($q) => $q->whereIn('slug', ['manager', 'administrator']))->count(),
            'sla' => $org->sla_minutes,
        ];
        $tabCounts = [
            'active' => User::forOrganization($org->id)->where('status', 'active')->count(),
            'paused' => User::forOrganization($org->id)->where('status', 'paused')->count(),
            'archived' => User::forOrganization($org->id)->where('status', 'archived')->count(),
            'invited' => User::forOrganization($org->id)->where('status', 'invited')->count(),
            'teams' => Team::forOrganization($org->id)->count(),
        ];

        return view('crm.users.index', [
            'users' => $users,
            'tab' => $tab,
            'stats' => $stats,
            'roles' => Role::forOrganization($org->id)->get(),
            'teams' => Team::forOrganization($org->id)->get(),
            'campaigns' => $org->campaigns,
            'teamList' => Team::forOrganization($org->id)->with('users', 'manager')->get(),
            'orgUsers' => User::forOrganization($org->id)->where('status', '!=', 'archived')->orderBy('name')->get(),
            'tabCounts' => $tabCounts,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $org = $request->user()->organization;
        $invitation = Invitation::create([
            'organization_id' => $org->id,
            'invited_by' => $request->user()->id,
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'team_id' => $request->team_id,
            'token' => Str::random(48),
            'message' => $request->message,
            'permissions' => $request->permissions,
            'expires_at' => now()->addDays(7),
        ]);

        User::create([
            'organization_id' => $org->id,
            'name' => $request->name,
            'email' => $request->email,
            'status' => 'invited',
            'invitation_token' => $invitation->token,
            'invited_at' => now(),
        ]);

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $request->email)
                ->notify(new GenericCrmNotification(
                    'You are invited to GrowLead CRM',
                    $request->message ?: 'You have been invited to join '.$org->name,
                    route('invitation.accept', $invitation->token)
                ));
        } catch (\Throwable $e) {
            // Mail may be unconfigured locally.
        }

        return back()->with('success', 'Invitation sent.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'status' => ['required', 'in:active,paused,archived,invited'],
            'role_id' => ['nullable', 'integer'],
            'team_id' => ['nullable', 'integer'],
        ]);
        $user->update($data);
        if ($request->role_id) {
            $user->roles()->sync([$request->role_id]);
        }

        return back()->with('success', 'User updated.');
    }

    public function profile(Request $request)
    {
        return view('crm.users.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $request->user()->update($data);

        return back()->with('success', 'Profile updated.');
    }
}
