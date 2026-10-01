@extends('layouts.crm')
@section('title', 'CSV Import')
@section('content')
@include('components.page-header', ['title' => 'CSV Import', 'subtitle' => 'Upload leads for '.$campaign->name])
<div class="crm-content">
    <div class="card-crm p-4">
        <p>Expected columns: first_name, last_name, email, phone, city, interested_in</p>
        <form method="POST" action="{{ route('crm.campaigns.import.store', $campaign) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" class="form-control mb-3" accept=".csv,text/csv" required>
            <button class="btn btn-primary">Upload and queue import</button>
        </form>
    </div>
</div>
@endsection
