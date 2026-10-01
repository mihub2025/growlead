<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform') — GrowLead</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/crm.css') }}?v=20260916super1">
    <link rel="stylesheet" href="{{ asset('assets/css/lumen.css') }}?v=20260916super1">
    @stack('styles')
</head>
<body>
<div class="app">
    @include('components.super-sidebar')
    <div class="main">
        @include('components.super-chrome')
        @yield('content')
    </div>
</div>

@if(session('success') || session('error') || session('status'))
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="crmToast" class="toast show" role="alert">
        <div class="toast-body fw-semibold">
            {{ session('success') ?: (session('error') ?: session('status')) }}
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/crm.js') }}?v=20260818btnfix"></script>
@stack('scripts')
</body>
</html>
