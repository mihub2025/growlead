@extends('layouts.super')
@section('title', 'New organization')
@section('content')
<div class="crm-topbar">
    <div>
        <p class="kicker">Platform</p>
        <h1 class="crm-title d-inline">Create organization</h1>
        <p class="crm-subtitle">Provisions a isolated workspace and its first administrator.</p>
    </div>
</div>
<div class="crm-content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('super.organizations.store') }}" class="card-crm settings-form-card p-4">
        @csrf
        <h5 class="mb-3">Workspace</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Organization name</label>
                <input name="organization_name" value="{{ old('organization_name') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Industry</label>
                <select name="industry" class="form-select" required>
                    @foreach(config('crm.industries') as $key => $label)
                        <option value="{{ $key }}" @selected(old('industry') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Country</label>
                <input name="country" value="{{ old('country') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Currency</label>
                @include('components.currency-select', ['value' => old('currency', 'USD')])
            </div>
            <div class="col-md-4">
                <label class="form-label">Timezone</label>
                <input name="timezone" value="{{ old('timezone', 'UTC') }}" class="form-control" required>
            </div>
        </div>

        <h5 class="mb-3 mt-4">Workspace administrator</h5>
        <p class="text-muted small">This person signs into the CRM for that organization. They cannot create other organizations.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full name</label>
                <input name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input name="phone" value="{{ old('phone') }}" class="form-control">
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary">Create organization</button>
            <a href="{{ route('super.organizations.index') }}" class="btn btn-outline-soft">Cancel</a>
        </div>
    </form>
</div>
@endsection
