@extends('layouts.legal')

@section('title', 'Terms of Service')
@section('meta_description', 'Terms of Service for '.$appName.'. Rules for using the CRM, connecting Meta/Facebook accounts, and managing workspace data.')

@section('content')
        <h1>Terms of Service</h1>
        <p class="updated">Last updated: {{ $updatedAt }}</p>

        <h2>Acceptance of Terms</h2>
        <p>
            These Terms of Service (“Terms”) govern access to and use of {{ $appName }}
            (“GrowLead”, “the service”, “we”, “us”). By creating an account, inviting users,
            or using the service, you agree to these Terms and to our
            <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.
            If you are using the service on behalf of a business, you represent that you have
            authority to bind that business.
        </p>

        <h2>Description of Service</h2>
        <p>
            {{ $appName }} is a workspace CRM for managing campaigns, leads, pipeline, tasks,
            reporting, and optional advertising integrations. Meta Ads connection, when configured,
            lets an authorized user sign in with Facebook, choose Business Managers, and import
            campaigns and Lead Ads into the CRM. Features may vary by organization settings and
            user permissions.
        </p>

        <h2>User Accounts</h2>
        <p>
            You must provide accurate registration information and keep your password confidential.
            Each login is intended for one person. Organization administrators are responsible for
            inviting users, assigning roles, and disabling or archiving accounts that should no
            longer have access. You are responsible for activity that occurs under your account.
        </p>

        <h2>Connected Facebook/Meta Accounts</h2>
        <p>
            Connecting Meta is optional. If you connect a Facebook account, you authorize
            {{ $appName }} to access data Meta returns for the permissions you approve, including
            Business Managers, ad accounts, campaigns, associated Pages used for Lead Ads, and
            lead form submissions. You must only connect accounts you are allowed to manage.
            We do not claim ownership of your Meta assets. You can disconnect Meta Ads from
            Integrations in the CRM. Disconnecting does not automatically delete leads or
            campaigns already imported; see our
            <a href="{{ route('data-deletion') }}">User Data Deletion Instructions</a>.
        </p>

        <h2>Authorized Use</h2>
        <p>
            You may use the service only for lawful business purposes and in line with Meta’s
            Platform Terms, advertising policies, and any other third-party terms that apply to
            accounts you connect. You must not attempt to access another organization’s workspace
            or data without permission.
        </p>

        <h2>User Responsibilities</h2>
        <ul>
            <li>Keep credentials secure and grant CRM and Meta access only to people who need it.</li>
            <li>Use imported lead data in accordance with applicable privacy and marketing laws, including consent where required.</li>
            <li>Review which Business Managers you select for sync so the CRM does not import accounts you do not intend to use.</li>
            <li>Maintain your own backups of critical business records if you need them outside the CRM.</li>
        </ul>

        <h2>Prohibited Activities</h2>
        <p>You must not:</p>
        <ul>
            <li>misuse, overload, or attempt to reverse engineer the service;</li>
            <li>upload unlawful, infringing, or deceptive content;</li>
            <li>use the service to send spam or to process data you do not have the right to process;</li>
            <li>circumvent permissions, authentication, or Meta’s access controls;</li>
            <li>resell or provide the service to third parties except as expressly allowed by the organization that operates this deployment.</li>
        </ul>

        <h2>Third-Party Platforms</h2>
        <p>
            Meta, email providers, and other integrations are third-party services. Their availability,
            policies, and APIs can change. {{ $appName }} is not responsible for outages, permission
            changes, or enforcement actions by those platforms. Your relationship with Meta remains
            governed by Meta’s terms.
        </p>

        <h2>Data and Content</h2>
        <p>
            Your organization owns the CRM records it creates or imports (leads, campaigns, notes,
            and similar content), subject to these Terms and our Privacy Policy. You grant us a
            limited license to host, process, and display that content solely to provide the service.
            We do not sell your content.
        </p>

        <h2>Availability</h2>
        <p>
            We aim to keep the service available but do not guarantee uninterrupted operation.
            Maintenance, hosting issues, Meta API limits, or events beyond our control may cause
            downtime or delayed syncs.
        </p>

        <h2>Termination</h2>
        <p>
            You may stop using the service at any time. Organization administrators may archive
            users. We may suspend or terminate access if these Terms are violated, if required
            by law, or if the deployment is discontinued. After termination, we may delete or
            restrict access to workspace data as described in the Privacy Policy, except where
            retention is required.
        </p>

        <h2>Limitation of Liability</h2>
        <p>
            To the fullest extent permitted by law, {{ $appName }} and its operators are not liable
            for indirect, incidental, special, consequential, or lost-profit damages, or for
            lost data, lead volume, or advertising results, arising from use of the service or
            from third-party platforms such as Meta. The service is provided “as is.” Some
            jurisdictions do not allow certain limitations; in those cases, our liability is
            limited to the maximum extent permitted.
        </p>

        <h2>Changes to Terms</h2>
        <p>
            We may update these Terms. The “Last updated” date will change when we do.
            Continued use after an update means you accept the revised Terms.
        </p>

        <h2>Contact</h2>
        <p>
            Questions about these Terms: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
        </p>
@endsection
