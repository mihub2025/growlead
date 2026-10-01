<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Privacy & Policies for GrowLead CRM — how we handle account data, workspace records, and Meta integrations.">
    <title>Privacy &amp; Policies — GrowLead</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v=20260930b">
    <link rel="icon" href="{{ asset('assets/images/growlead.png') }}">
</head>
<body class="landing-body">
<header class="landing-nav">
    <div class="landing-nav__inner">
        <a class="landing-brand" href="{{ route('home') }}">
            <img src="{{ asset('assets/images/growlead.png') }}" alt="GrowLead">
            <span>GrowLead</span>
        </a>
        <button class="landing-nav__toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <ul class="landing-nav__links" data-nav-links>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('contact-us') }}">Contact Us</a></li>
            <li><a class="is-active" href="{{ route('privacy-policies') }}">Privacy &amp; Policies</a></li>
            <li><a class="landing-nav__cta" href="{{ route('login') }}">Login</a></li>
        </ul>
    </div>
</header>

<main>
    <section class="landing-section landing-section--dark" style="padding-top: 3.5rem;">
        <div class="landing-wrap">
            <div class="landing-section__head">
                <h2>Privacy &amp; Policies</h2>
                <p>How GrowLead handles account data, workspace records, and Meta integrations.</p>
            </div>

            <div class="landing-policy">
                <article>
                    <h3>What GrowLead is</h3>
                    <p>
                        {{ $appName }} (“GrowLead”) is a customer relationship management platform for
                        businesses to manage campaigns, leads, pipeline activity, tasks, and optional
                        advertising integrations such as Meta Ads / Lead Ads and Conversion API (CAPI).
                    </p>
                </article>

                <article>
                    <h3>Information we collect</h3>
                    <ul>
                        <li><strong>Account information</strong> — name, email, password, organization details, and similar profile fields.</li>
                        <li><strong>Workspace data</strong> — campaigns, leads, notes, activities, tasks, opportunities, tags, and team membership.</li>
                        <li><strong>Lead submissions</strong> — name, email, phone, and other fields from forms or Meta Lead Ads.</li>
                        <li><strong>Usage data</strong> — login sessions and server logs needed to operate and secure the service.</li>
                    </ul>
                </article>

                <article>
                    <h3>Meta / Facebook data</h3>
                    <p>
                        If an authorized user connects Meta Ads, GrowLead receives only the data Meta returns
                        for the permissions that user approves. This may include Business Managers, ad accounts,
                        campaigns, Pages used for Lead Ads, lead form submissions, and an encrypted access token
                        so sync can continue. We use this data only to provide CRM features — not to publish posts,
                        send Messenger messages, or run ads on your behalf.
                    </p>
                </article>

                <article>
                    <h3>How we use information</h3>
                    <ul>
                        <li>Operate organization workspaces and user accounts</li>
                        <li>Import and manage campaigns and leads from connected Meta accounts</li>
                        <li>Assign, route, report on, and follow up leads inside the CRM</li>
                        <li>Send qualified outcomes back to Meta when CAPI is configured</li>
                        <li>Maintain security, debug integrations, and improve the product</li>
                    </ul>
                </article>

                <article>
                    <h3>Sharing, security &amp; retention</h3>
                    <p>
                        We do not sell personal information. Data may be shared with members of your organization
                        according to their roles, with infrastructure providers needed to host the service, with Meta
                        when you connect an account, or when required by law. Integration secrets are encrypted at rest.
                        Records are retained while the organization uses the workspace, or until a valid deletion
                        request is processed.
                    </p>
                </article>

                <article>
                    <h3>Your rights &amp; deletion</h3>
                    <p>
                        You may request access, correction, or deletion of personal information we hold about you.
                        Follow our
                        <a href="{{ route('data-deletion') }}">User Data Deletion Instructions</a>
                        or reach us through the
                        <a href="{{ route('contact-us') }}">Contact Us</a> form
                        @if (! empty($contactEmail))
                            / <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                        @endif
                        .
                    </p>
                </article>

                <article>
                    <h3>Terms highlights</h3>
                    <ul>
                        <li>Use GrowLead only for lawful business purposes and keep account credentials secure.</li>
                        <li>Connect only Meta accounts you are authorized to manage; disconnect anytime from Integrations.</li>
                        <li>Imported lead data must be used in line with applicable privacy and marketing laws.</li>
                        <li>Do not misuse the service, spam, or attempt to access another organization’s workspace.</li>
                    </ul>
                </article>

                <div class="landing-policy__links">
                    <a href="{{ route('privacy-policy') }}">Full Privacy Policy</a>
                    <a href="{{ route('terms') }}">Terms of Service</a>
                    <a href="{{ route('data-deletion') }}">Data Deletion</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-wrap landing-footer__inner">
        <div>&copy; {{ date('Y') }} GrowLead. All rights reserved.</div>
        <div class="landing-footer__links">
            <a href="{{ route('contact-us') }}">Contact Us</a>
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
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }
})();
</script>
</body>
</html>
