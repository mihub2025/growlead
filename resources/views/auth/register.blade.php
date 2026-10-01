@extends('layouts.auth')
@section('title', 'Register')
@section('content')
<h2 class="fw-bold mb-1">Create your workspace</h2>
<p class="text-muted mb-4">Register your organization on GrowLead CRM</p>
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Organization Name</label><input name="organization_name" value="{{ old('organization_name') }}" class="form-control" required></div>
        <div class="col-md-6">
            <label class="form-label">Industry</label>
            <select name="industry" class="form-select">
                @foreach(config('crm.industries') as $key => $label)
                    <option value="{{ $key }}" @selected(old('industry')===$key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Country</label><input name="country" value="{{ old('country') }}" class="form-control"></div>
        <div class="col-md-6">
            <label class="form-label">Currency</label>
            @include('components.currency-select', ['value' => old('currency', 'USD')])
        </div>
        <div class="col-md-6"><label class="form-label">Timezone</label><input name="timezone" value="{{ old('timezone', 'UTC') }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    </div>
    <button class="btn btn-primary w-100 py-2 fw-bold mt-3">Create Account</button>
</form>
<p class="mt-3 mb-0 text-center">Already registered? <a href="{{ route('login') }}">Login</a></p>
@endsection
