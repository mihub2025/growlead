@extends('layouts.auth')
@section('title', 'Accept Invitation')
@section('content')
<h2 class="fw-bold mb-3">Join {{ $invitation->organization->name }}</h2>
<p class="text-muted">Set a password for {{ $invitation->email }}</p>
<form method="POST" action="{{ route('invitation.accept.store', $invitation->token) }}">
    @csrf
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-primary w-100">Accept invitation</button>
</form>
@endsection
