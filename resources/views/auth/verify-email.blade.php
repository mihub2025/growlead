@extends('layouts.auth')
@section('title', 'Verify Email')
@section('content')
<h2 class="fw-bold mb-3">Verify your email</h2>
<p>Please verify your email address to continue using GrowLead CRM.</p>
<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button class="btn btn-primary w-100">Resend verification email</button>
</form>
@endsection
