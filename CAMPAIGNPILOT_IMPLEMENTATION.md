# CampaignPilot CRM — Implementation

## Architecture

Laravel 10 multi-tenant CRM. Every business record is scoped by `organization_id` resolved from the authenticated user, never from a browser-supplied ID.

Stack: Laravel 10, MySQL, Blade, Bootstrap 5 CDN, Bootstrap Icons, vanilla JS, Chart.js CDN, Leaflet CDN. No React, Vue, Tailwind, Inertia, NPM, or Vite.

## Installation

See `README.md`. After migrate + seed, log in as `admin@campaignpilot.test` / `password`.

## Authentication

Session auth with CSRF. Routes: `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/email/verify`, `POST /logout`. Registration creates the organization, administrator role, default pipeline, tags, sources, and settings.

## Database

Core tables: organizations, users, roles, permissions, teams, lead_sources, pipelines, pipeline_stages, tags, offerings, campaigns, leads, opportunities, lead_activities, lead_notes, tasks, attachments, custom_fields, automation_rules, integrations, webhook_logs, ai_insights, duplicate_candidates, audit_logs, invitations, csv_imports, jobs, notifications.

## Organizations

One organization per registration. Users cannot see another organization's leads, campaigns, or reports. Policies enforce this on every show/update/delete.

## Roles and permissions

Default roles: Administrator, Manager, Team Lead, Agent, Viewer. Gates and the `permission:` middleware protect routes. Hiding a button is not enough.

## Leads

Full CRUD, search, filters, tabs, bulk assign/status/stage/tags, export, notes, activities, tasks, attachments, merge, and Lead 360. Duplicate detection uses normalized phone, WhatsApp, email, and external ID.

## Campaigns

Campaign Center, 8-step wizard, assignment, routing (`LeadRoutingService`: round robin, least assigned, performance, location, weighted), CSV import via queue, and campaign detail.

## AI

`AI_ENABLED=false` by default. When disabled, scoring/summaries use rule-based fallbacks and the CRM still works. Never hardcode API keys. Provider interface lives in `App\Services\AI`.

## Reports

Executive, sales, marketing, user, pipeline, and custom tabs. KPIs and tables come from database queries. CSV export available.

## Automations

Trigger/condition/action engine with run logs. Listeners fire on lead and task events.

## Integrations

Card UI for Meta, Instagram, TikTok, Google, LinkedIn, website, webhook, WhatsApp, email, Bayut, Dubizzle. Credentials are encrypted. WhatsApp never pretends a message was sent if disconnected.

## Webhooks

`POST /api/v1/leads/webhook/{source}`

Headers/query: `X-Webhook-Token` or `token`, `X-Organization` or `org` (organization slug). Token is stored in organization settings as `webhook_token`. Rate limited. Processing is queued.

## CSV Import

Upload on a campaign. Job `ProcessCsvImport` chunks rows and creates leads.

## Queue

`QUEUE_CONNECTION=database`. Jobs: CSV import, webhook processing. Run `php artisan queue:work`.

## Scheduler

- `crm:monitor-sla` every 5 minutes
- `crm:overdue-tasks` every 15 minutes

## Email

Laravel notifications for assignments, invitations, SLA, imports. Use `MAIL_MAILER=log` locally.

## File storage

Attachments use Laravel Storage (`local` disk) with MIME/size validation.

## Seed credentials

`admin@campaignpilot.test` / `password`

## Environment variables

See `.env.example`. AI and mail secrets stay empty until you add real providers.

## API endpoints

- `POST /api/v1/leads/webhook/{source}`
- `GET /api/user` (Sanctum)

## Deployment commands

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work
```

Do not run `migrate:fresh` in production.

## Testing

```bash
php artisan test
```

Feature coverage: register/login/logout, protected routes, lead CRUD/search, org isolation, campaign create, webhook auth.

## Security

Auth, CSRF, policies, org scoping, Form Requests, upload validation, webhook tokens + throttle, encrypted integration credentials, audit logs. API keys and passwords are never rendered.

## Pending API credentials

Meta, TikTok, Google, WhatsApp, and OpenAI keys are not included. Connect them from Integrations / `.env` when available.

## Production checklist

- Set `APP_DEBUG=false` and a real `APP_KEY`
- Use a real mailer and queue worker
- Configure HTTPS and `APP_URL`
- Add scheduler cron
- Store integration secrets only in encrypted credentials / env
- Review roles before inviting users
