<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GrowLead CRM — manage campaigns, leads, and Meta Lead Ads in one workspace.">
    <title>GrowLead — Campaign & Lead CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v=20260930b">
    <link rel="icon" href="{{ asset('assets/images/growlead.png') }}">
</head>
<body class="landing-body">
<header class="landing-nav">
    <div class="landing-nav__inner">
        <a class="landing-brand" href="{{ route('home') }}" data-scroll="home">
            <img src="{{ asset('assets/images/growlead.png') }}" alt="GrowLead">
            <span>GrowLead</span>
        </a>
        <button class="landing-nav__toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <ul class="landing-nav__links" data-nav-links>
            <li><a href="{{ route('home') }}" data-scroll="home">Home</a></li>
            <li><a href="{{ route('home') }}" data-scroll="contact">Contact Us</a></li>
            <li><a href="{{ route('privacy-policies') }}">Privacy &amp; Policies</a></li>
            <li><a class="landing-nav__cta" href="{{ route('login') }}">Login</a></li>
        </ul>
    </div>
</header>

<main>
    <section class="landing-hero" id="home">
        <div class="landing-hero__glow" aria-hidden="true"></div>
        <div class="landing-wrap landing-hero__grid">
            <div>
                <span class="landing-kicker">Workspace CRM</span>
                <h1>GrowLead<em>.</em></h1>
                <p class="landing-hero__lead">
                    Run campaigns, capture Meta Lead Ads, and move every lead through your pipeline —
                    from first contact to closed deal — in one focused workspace.
                </p>
                <div class="landing-hero__actions">
                    <a class="btn-gl btn-gl--primary" href="{{ route('login') }}">Login</a>
                    <a class="btn-gl btn-gl--ghost" href="{{ route('home') }}" data-scroll="contact">Contact Us</a>
                </div>
            </div>
            <div class="landing-hero__mark">
                <img src="{{ asset('assets/images/growlead.png') }}" alt="GrowLead logo">
            </div>
        </div>
    </section>

    <section class="landing-section" id="contact">
        <div class="landing-wrap">
            <div class="landing-section__head">
                <h2>Contact Us</h2>
                <p>Send a message and our team will respond as soon as possible.</p>
            </div>

            @if (session('contact_success'))
                <div class="landing-alert landing-alert--ok" role="status">{{ session('contact_success') }}</div>
            @endif

            @if ($errors->any())
                <div class="landing-alert landing-alert--err" role="alert">
                    Please fix the highlighted fields and try again.
                </div>
            @endif

            <form class="landing-form" method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf
                <label>
                    Name
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                    @error('name') <span class="landing-field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                    @error('email') <span class="landing-field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Message
                    <textarea name="message" required maxlength="5000">{{ old('message') }}</textarea>
                    @error('message') <span class="landing-field-error">{{ $message }}</span> @enderror
                </label>
                <button class="btn-gl btn-gl--primary" type="submit">Send message</button>
            </form>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-wrap landing-footer__inner">
        <div>&copy; {{ date('Y') }} GrowLead. All rights reserved.</div>
        <div class="landing-footer__links">
            <a href="{{ route('privacy-policies') }}">Privacy &amp; Policies</a>
            <a href="{{ route('terms') }}">Terms</a>
            <a href="{{ route('login') }}">Login</a>
        </div>
    </div>
</footer>

<script>
(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const links = document.querySelector('[data-nav-links]');

  const scrollToId = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  document.querySelectorAll('[data-scroll]').forEach((a) => {
    a.addEventListener('click', (e) => {
      const id = a.getAttribute('data-scroll');
      if (!id || !document.getElementById(id)) return;
      e.preventDefault();
      scrollToId(id);
      if (links) {
        links.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
      }
      if (window.location.hash) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
    });
  });

  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  @if (session('contact_success') || $errors->any())
  scrollToId('contact');
  @endif

  const params = new URLSearchParams(window.location.search);
  if (params.get('focus') === 'contact') {
    scrollToId('contact');
    history.replaceState(null, '', window.location.pathname);
  }
})();
</script>
</body>
</html>
