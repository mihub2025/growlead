<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->q);
        $org = $request->user()->organization_id;
        $leads = $q ? Lead::forOrganization($org)->where(function ($inner) use ($q) {
            $inner->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%");
        })->limit(8)->get() : collect();
        $campaigns = $q ? Campaign::forOrganization($org)->where('name', 'like', "%{$q}%")->limit(5)->get() : collect();
        $users = $q ? User::forOrganization($org)->where('name', 'like', "%{$q}%")->limit(5)->get() : collect();

        return view('crm.search.index', compact('q', 'leads', 'campaigns', 'users'));
    }
}
