<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — GrowLead CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/crm.css') }}?v=20260818reports1">
    <link rel="stylesheet" href="{{ asset('assets/css/lumen.css') }}?v=20260818reports1">
</head>
<body class="crm-body">
<div class="auth-wrap">
    <div class="auth-left">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="crm-logo"><i class="bi bi-broadcast"></i></div>
            <div>
                <div class="fw-bold" style="font-size:18px;letter-spacing:-.03em">GrowLead</div>
                <small style="color:#c4a574;font-weight:600">Workspace CRM</small>
            </div>
        </div>
        <h1 class="serif mb-3" style="font-size:42px;line-height:1.1">Manage campaigns.<br>Convert more leads.</h1>
        <p class="fs-6" style="color:#c9c2b4;max-width:420px">A warm, focused workspace for campaigns, pipeline, and the people who close them.</p>
    </div>
    <div class="auth-right">
        <div class="auth-card">
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
