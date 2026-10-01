@extends('layouts.legal')

@section('title', 'User Data Deletion Instructions')
@section('meta_description', 'How to request deletion of '.$appName.' account data and Meta/Facebook connection data.')

@section('content')
        <h1>User Data Deletion Instructions</h1>
        <p class="updated">Last updated: {{ $updatedAt }}</p>

        <p>
            This page explains how to request deletion of data associated with your
            {{ $appName }} account and any connected Facebook/Meta account. It is provided
            to meet Meta app requirements and to help users exercise deletion rights.
        </p>

        <h2>Method 1 — Disconnect Meta from the CRM</h2>
        <p>
            If you have a {{ $appName }} login and permission to manage integrations:
        </p>
        <ul>
            <li>Sign in to {{ $appName }}.</li>
            <li>Open <strong>Integrations</strong>.</li>
            <li>On the <strong>Meta Ads</strong> card, click <strong>Disconnect</strong>.</li>
        </ul>
        <p>
            Disconnecting removes the stored Meta access token and connection settings for
            that organization’s Meta integration (including the connected Facebook user id
            and saved Business Manager selection). Sync of campaigns and Lead Ads from Meta
            stops. Campaigns and leads already imported into the CRM are not automatically
            deleted by Disconnect; use Method 2 if you need those records removed.
        </p>
        <p>
            Organization administrators can also archive a user under <strong>Users</strong>,
            which disables that person’s CRM access. Archiving a user is not the same as
            deleting all organization data.
        </p>

        <h2>Method 2 — Email a deletion request</h2>
        <p>
            To request deletion of stored CRM and Meta-linked data, email
            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
            and include:
        </p>
        <ul>
            <li>your name;</li>
            <li>your CRM account email;</li>
            <li>the connected Facebook Page or ad account name, if applicable;</li>
            <li>a clear request to delete your data.</li>
        </ul>
        <p>
            We may need to verify that the request comes from the account holder or from
            an organization administrator before we act on it.
        </p>

        <h2>What happens after a deletion request</h2>
        <p>After we verify the request, we will, where applicable:</p>
        <ul>
            <li>remove or disconnect stored Meta/Facebook tokens and integration credentials;</li>
            <li>remove account-linked Meta connection settings (connected user, selected businesses, and cached business lists);</li>
            <li>delete or anonymize eligible CRM records associated with the request, subject to legal, security, or accounting retention requirements;</li>
            <li>confirm when the request has been processed, if you provided a working email address.</li>
        </ul>
        <p>
            Some records may be retained where the organization must keep them (for example
            leads that belong to a business workspace, or information we are required to keep
            by law). We will explain if part of a request cannot be completed in full.
        </p>

        <h2>Revoking Facebook access</h2>
        <p>
            Users may also revoke this application's access from their Facebook account settings.
            Revoking access stops future access but may not automatically delete data already stored
            in the CRM. To request deletion of stored data, follow the instructions on this page.
        </p>
        <p>
            In Facebook, this is typically under <strong>Settings → Apps and websites</strong>
            (or <strong>Business integrations</strong>), then remove {{ $appName }} / the Meta app
            used by this CRM.
        </p>

        <h2>Contact</h2>
        <p>
            Deletion and privacy requests:
            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
        </p>
        <p>
            Related documents:
            <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
            and
            <a href="{{ route('terms') }}">Terms of Service</a>.
        </p>
@endsection
