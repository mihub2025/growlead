<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <meta name="description" content="@yield('meta_description')">
    <title>@yield('title') — {{ $appName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#161513; --muted:#6f6b64; --line:#e6e1d8; --bg:#f3f1ec; --card:#fffcf7; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.65;
        }
        .legal {
            max-width: 760px;
            margin: 0 auto;
            padding: 40px 22px 72px;
        }
        .legal-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .02em;
            text-decoration: none;
        }
        .brand span {
            width: 28px; height: 28px; border-radius: 8px;
            background: var(--ink); color: #f6f1e8;
            display: grid; place-items: center; font-size: 12px;
        }
        .legal-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 14px 18px;
            font-size: 13px;
            font-weight: 650;
        }
        .legal-nav a { color: var(--muted); text-decoration: none; }
        .legal-nav a:hover,
        .legal-nav a.is-active { color: var(--ink); }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 36px 40px;
            box-shadow: 0 1px 1px rgba(22,21,19,.04), 0 8px 24px rgba(22,21,19,.05);
        }
        h1 {
            font-family: "Instrument Serif", Georgia, serif;
            font-size: 40px;
            font-weight: 400;
            letter-spacing: -.03em;
            margin: 0 0 8px;
        }
        h2 { font-size: 18px; margin: 28px 0 8px; }
        p, li { margin: 0 0 14px; color: #3d3a35; }
        ul { margin: 0 0 14px; padding-left: 1.2em; }
        li { margin-bottom: 8px; }
        .updated { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 22px; }
        a { color: var(--ink); font-weight: 650; }
        .legal-footer {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
        }
        .legal-footer nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
            margin-bottom: 10px;
        }
        .legal-footer a { color: var(--muted); text-decoration: none; font-weight: 650; }
        .legal-footer a:hover { color: var(--ink); }
        @media (max-width: 640px) {
            .card { padding: 24px 20px; }
            h1 { font-size: 32px; }
        }
    </style>
</head>
<body>
<div class="legal">
    <header class="legal-top">
        <a class="brand" href="{{ url('/') }}"><span>CP</span> {{ $appName }}</a>
        <nav class="legal-nav" aria-label="Legal">
            <a href="{{ route('privacy-policy') }}" class="{{ request()->routeIs('privacy-policy') ? 'is-active' : '' }}">Privacy Policy</a>
            <a href="{{ route('terms') }}" class="{{ request()->routeIs('terms') ? 'is-active' : '' }}">Terms of Service</a>
            <a href="{{ route('data-deletion') }}" class="{{ request()->routeIs('data-deletion') ? 'is-active' : '' }}">Data Deletion</a>
        </nav>
    </header>
    <article class="card">
        @yield('content')
    </article>
    <footer class="legal-footer">
        <nav aria-label="Legal footer">
            <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
            <a href="{{ route('terms') }}">Terms of Service</a>
            <a href="{{ route('data-deletion') }}">User Data Deletion</a>
        </nav>
        <div>&copy; {{ date('Y') }} {{ $appName }}. Questions: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></div>
    </footer>
</div>
</body>
</html>
