@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<h2 class="fw-bold mb-1">Welcome back</h2>
<p class="text-muted mb-4">Sign in to GrowLead CRM</p>
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="d-flex justify-content-between mb-3">
        <label class="form-check">
            <input type="checkbox" name="remember" class="form-check-input"> Remember Me
        </label>
        <a href="{{ route('password.request') }}">Forgot Password</a>
    </div>
    <button class="btn btn-primary w-100 py-2 fw-bold">Login</button>
</form>
<p class="mt-3 mb-0 text-center text-muted">Organization accounts are created by a super administrator.</p>
@endsection
