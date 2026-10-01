<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Services\PlatformOrganizationService;

class DashboardController extends Controller
{
    public function index(PlatformOrganizationService $platform)
    {
        return view('super.dashboard', $platform->overview());
    }
}
