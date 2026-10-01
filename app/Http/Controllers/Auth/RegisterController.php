<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function show()
    {
        return redirect()->route('login')->with('status', 'New organizations can only be created by a super administrator.');
    }

    public function store(Request $request)
    {
        return redirect()->route('login')->withErrors([
            'email' => 'Public registration is disabled. Ask a super administrator to create your organization.',
        ]);
    }
}
