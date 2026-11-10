# MCC-FERMS
Facility Equipment Reservation Management System for Mabalacat City College (Laravel 12, Blade, Supabase PostgreSQL).

## Requirements
PHP 8.2+ (8.3 recommended) with `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`; Composer 2; Node.js 20+.

## Install
```bash
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
```
Edit `.env`: set the Supabase **session pooler** values (`DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`) and, for Microsoft sign-in, `MICROSOFT_*` and `MCC_EMAIL_DOMAIN`. Never commit `.env`.

```bash
php scripts/verify-connection.php    # optional: confirm Supabase connection
php artisan migrate --seed           # creates tables, enables btree_gist, seeds demo data
php artisan serve                    # http://localhost:8000
```
With `APP_ENV=local`, `/login` shows development logins for Student, Organization, Staff and Admin (seeded `*@mcc.edu.ph`).

## Tests
`php artisan test` (uses in-memory sqlite; PostgreSQL-only constraints are skipped there).

## Layout
`app/Http/Controllers` (Auth, Student, Admin) · `app/Http/Middleware/RoleMiddleware.php` · `app/Models` · `database/migrations|seeders` · `resources/views/{layouts,auth,student,admin}` · `public/css/ferms.css` · `scripts/` · `tests/Feature`

## Not yet implemented
Calendar-grid schedule views and a screen for `facility_schedules` blocks, admin settings page, REST API, pagination.
