@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('content')
<h2 class="fw-bold mb-3">Forgot password</h2>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <button class="btn btn-primary w-100">Send reset link</button>
</form>
<p class="mt-3 mb-0"><a href="{{ route('login') }}">Back to login</a></p>
@endsection
