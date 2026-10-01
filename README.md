# GrowLead CRM

Generic multi-business Campaign + Lead Management CRM built on Laravel 10, Blade, and Bootstrap 5. No NPM or Vite build is required.

## Local setup

1. Create the MySQL database `campaignpilot`.
2. Copy `.env.example` to `.env` and set database credentials.
3. Run:

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

4. Open `http://localhost/compaign/public` (XAMPP) or `php artisan serve`.

## Demo credentials

Platform super admin (creates and monitors organizations):

- Email: `superadmin@campaignpilot.test`
- Password: `password`

Organization admin (Northstar Growth workspace):

- Email: `admin@campaignpilot.test`
- Password: `password`

Public registration is disabled. New organizations can only be created from `/super/organizations/create`.

## Scheduler

Add this cron entry in production:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Also run a queue worker:

```bash
php artisan queue:work
```

See `CAMPAIGNPILOT_IMPLEMENTATION.md` for architecture, modules, webhooks, and the production checklist.
