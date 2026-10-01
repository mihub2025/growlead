@extends('layouts.crm')
@section('title', 'Profile')
@section('content')
@include('components.page-header', ['title' => 'Profile', 'subtitle' => 'Update your GrowLead account.'])
<div class="crm-content">
    <div class="card-crm p-3" style="max-width:560px">
        <form method="POST" action="{{ route('crm.settings.profile.update') }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Name</label><input name="name" value="{{ $user->name }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input value="{{ $user->email }}" class="form-control" disabled></div>
            <div class="mb-3"><label class="form-label">Phone</label><input name="phone" value="{{ $user->phone }}" class="form-control"></div>
            <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="password" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control"></div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
