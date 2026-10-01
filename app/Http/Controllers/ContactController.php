<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('crm.privacy_email');

        Mail::to($to)->send(new ContactMessageMail(
            $data['name'],
            $data['email'],
            $data['message'],
        ));

        return redirect()
            ->route('contact-us')
            ->with('contact_success', 'Thanks — your message has been sent. We will get back to you soon.');
    }
}
