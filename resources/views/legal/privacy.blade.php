@extends('layouts.legal')

@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy Policy for '.$appName.'. How we collect, use, store, and delete information, including data authorized through Meta/Facebook APIs.')

@section('content')
        <h1>Privacy Policy</h1>
        <p class="updated">Last updated: {{ $updatedAt }}</p>

        <h2>Introduction</h2>
        <p>
            {{ $appName }} (“GrowLead”, “we”, “us”) is a customer relationship management platform
            used by businesses to manage campaigns, leads, pipeline activity, and connected advertising
            accounts. This Privacy Policy explains what information we collect, how we use it, and the
            choices available to you.
        </p>
        <p>
            By creating an account or using the service, you agree to this policy. If you do not agree,
            do not use {{ $appName }}.
        </p>

        <h2>Information We Collect</h2>
        <p>We collect information that you or your organization provide, and information created while you use the product:</p>
        <ul>
            <li><strong>Account information</strong> — name, email address, password, phone number (if added), organization name, industry, timezone, and currency.</li>
            <li><strong>Workspace data</strong> — campaigns, leads, notes, activities, tasks, opportunities, tags, custom fields, team membership, and similar CRM records your organization stores in the product.</li>
            <li><strong>Lead form submissions</strong> — name, email, phone number, and other fields submitted through online forms or Meta Lead Ads.</li>
            <li><strong>Usage and technical data</strong> — login sessions, last-active timestamps, and server logs needed to operate and secure the service.</li>
        </ul>

        <h2>Facebook/Meta Information</h2>
        <p>
            If an authorized user connects Meta Ads, {{ $appName }} receives only the data Meta returns
            for permissions the user approves. Depending on that authorization, this may include:
        </p>
        <ul>
            <li>the Facebook user’s name and user ID;</li>
            <li>a Meta access token, stored encrypted, so the connection can stay active and sync;</li>
            <li>Business Managers, ad accounts, and related names, IDs, currency, and status;</li>
            <li>ad campaign names, status, budgets, dates, and performance insights;</li>
            <li>Facebook Pages associated with selected businesses, used to locate Lead Ads forms;</li>
            <li>Lead Ads form names and lead submissions (field data such as name, email, and phone).</li>
        </ul>
        <p>
            We process Meta/Facebook API data only to provide CRM functionality: connecting ad accounts,
            importing campaigns, importing leads, matching those leads to campaigns, and related
            lead-management in the workspace. We do not use Meta APIs to send Facebook messages,
            publish posts, or run ads on your behalf.
        </p>

        <h2>How We Use Information</h2>
        <p>We use information to:</p>
        <ul>
            <li>create and operate organization workspaces and user accounts;</li>
            <li>respond to inquiries and help teams contact prospective customers;</li>
            <li>import and display campaigns and leads from connected Meta accounts;</li>
            <li>assign, score, route, and report on leads inside the CRM;</li>
            <li>maintain security, debug connection issues, and improve the service;</li>
            <li>comply with law and enforce our Terms of Service.</li>
        </ul>

        <h2>Facebook Pages and Connected Accounts</h2>
        <p>
            After a user connects Meta, they choose which Business Managers (and personal ad accounts,
            if listed) {{ $appName }} should sync. We list Pages tied to those businesses only so we can
            retrieve Lead Ads forms and import leads. Page names and IDs may be stored with imported
            lead records. Disconnecting Meta stops further Page and ad-account access.
        </p>

        <h2>Messages and CRM Data</h2>
        <p>
            Notes, activities, tasks, and other CRM records that users enter are stored in the
            organization’s workspace. {{ $appName }} does not read or send Facebook Messenger
            conversations. Other channels shown in Integrations (for example WhatsApp) are optional
            connections configured by the organization and are used only if that organization supplies
            its own credentials.
        </p>

        <h2>Data Storage</h2>
        <p>
            Information is stored in our application database and related server storage used to run
            {{ $appName }}. Meta access tokens and similar integration secrets are encrypted at rest
            in the application. Hosting may be provided by the organization that operates this
            deployment (for example on the domain where you access the CRM).
        </p>

        <h2>Data Sharing</h2>
        <p>
            We do not sell personal information. Information may be shared only:
        </p>
        <ul>
            <li>with members of your organization according to their CRM roles and permissions;</li>
            <li>with infrastructure and email providers needed to host and operate the service;</li>
            <li>with Meta, when you choose to connect an account, as part of Facebook Login and the Graph API;</li>
            <li>when required by law, or to protect the rights, safety, or security of users and the service.</li>
        </ul>

        <h2>Data Security</h2>
        <p>
            We use reasonable administrative and technical measures, including encrypted storage of
            integration credentials, authenticated sessions, and role-based access inside the CRM.
            No method of transmission or storage is completely secure. Please use a strong password
            and grant Meta access only to users who should manage advertising integrations.
        </p>

        <h2>Data Retention</h2>
        <p>
            We retain account, campaign, and lead records for as long as the organization keeps using
            the workspace, or until a valid deletion request is processed, unless a longer period is
            required for legal, security, or dispute-resolution reasons. Meta connection data is kept
            while the integration remains connected and is cleared from the integration record when
            Meta Ads is disconnected.
        </p>

        <h2>User Rights</h2>
        <p>
            Depending on applicable law, you may request access, correction, or deletion of personal
            information we hold about you. Organization administrators can update many workspace
            records directly in the CRM. For Meta-connected data and account-level requests, follow
            the <a href="{{ route('data-deletion') }}">User Data Deletion Instructions</a> or email us
            at <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
        </p>

        <h2>Data Deletion</h2>
        <p>
            You may request deletion of your personal information by following our
            <a href="{{ route('data-deletion') }}">User Data Deletion Instructions</a>
            or by contacting us at: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
        </p>

        <h2>Third-Party Services</h2>
        <p>
            Meta/Facebook is a third-party platform. Their collection and use of data is governed by
            Meta’s own policies. Optional integrations (such as other ad platforms or messaging tools)
            are similarly governed by those providers. We are not responsible for third-party practices.
        </p>

        <h2>Changes to This Privacy Policy</h2>
        <p>
            We may update this policy from time to time. The “Last updated” date at the top of this
            page will change when we do. Continued use of {{ $appName }} after an update constitutes
            acceptance of the revised policy.
        </p>

        <h2>Contact Us</h2>
        <p>
            For privacy-related questions, contact us at:
            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
        </p>
@endsection
