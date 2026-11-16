# MCC-FERMS
Facility Equipment Reservation Management System for Mabalacat City College (Laravel 12, Blade, Supabase PostgreSQL).

## Requirements
PHP 8.2+ (8.3 recommended) with `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`; Composer 2; Node.js 20+.

## Run locally

Install the PHP extensions listed in Requirements, Composer 2, Node.js 20+, and npm. Configure a PostgreSQL database (the project uses Supabase) before running migrations.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
```

Edit `.env` with the Supabase session-pooler values (`DB_HOST`, `DB_USERNAME`, and `DB_PASSWORD`). For local Microsoft sign-in, register `http://localhost:8000/auth/microsoft/callback` as a **Web** redirect URI in Microsoft Entra ID and set the `MICROSOFT_*` values and `MCC_EMAIL_DOMAIN`. Never commit `.env`.

For a new database, run the migrations and seed demo data once:

```bash
php scripts/verify-connection.php    # optional: confirm the database connection
php artisan migrate --seed
```

Start the app:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Open `http://localhost:8000`. For frontend hot reload during development, run `npm run dev` in a second terminal. With `APP_ENV=local`, the login page also shows development login buttons.

## Run in GitHub Codespaces over HTTPS

Open the repository in a GitHub Codespace and run **Dev Containers: Reopen in Container**. The `.devcontainer/Dockerfile` installs the app's PHP extensions, Composer, and Node.js. The configured `postCreateCommand` runs `scripts/setup.sh`; enter your Supabase and Microsoft settings in the Codespace `.env` (do not commit it).

For a new database, migrate and seed it once. Start the web server:

```bash
php artisan migrate --seed    # only for a new database
php artisan serve --host=0.0.0.0 --port=8000
```

Codespaces forwards port `8000` and provides an HTTPS URL such as `https://<codespace-name>-8000.app.github.dev`. In the **Ports** tab, change port `8000` visibility to **Public** to let groupmates access it without signing into GitHub. HTTPS is handled by Codespaces; Laravel serves HTTP inside the container.

For Microsoft sign-in through Codespaces, copy the forwarded URL and set these values in the Codespace `.env`:

```env
APP_URL=https://<codespace-name>-8000.app.github.dev
MICROSOFT_REDIRECT_URI=https://<codespace-name>-8000.app.github.dev/auth/microsoft/callback
SESSION_SECURE_COOKIE=true
```

Replace the example host with the exact URL shown in the **Ports** tab. Add the full callback URL as a **Web** redirect URI in Microsoft Entra ID, then run `php artisan config:clear` and restart `php artisan serve`. If the Codespace URL changes, update both places. The public site is available only while the Codespace is running; anyone with the public URL can reach this development app, so use test data.

## Deploy to Render

The repository includes a production Docker image and Render Blueprint. In Render, create a **Blueprint** from this repository and review the service and its paid instance plan before confirming deployment. The service builds Laravel assets and PHP dependencies into the image and serves Laravel's `public` directory over port `10000`. Render checks `/up` for health.

Before deploying, configure the Blueprint's `sync: false` variables in Render:

- `APP_KEY`: generate a fresh key with `php artisan key:generate --show` in a trusted local environment. Keep it private and do not change it after users have sessions or encrypted data.
- `APP_URL`: the full HTTPS Render URL or your custom domain.
- `DB_HOST`, `DB_USERNAME`, and `DB_PASSWORD`: use the Supabase **session pooler** connection values from the Supabase dashboard. Keep `DB_PORT=5432`, `DB_DATABASE=postgres`, and `DB_SSLMODE=require`; verify the values match your Supabase connection settings.
- `MICROSOFT_CLIENT_ID` and `MICROSOFT_CLIENT_SECRET`: the Entra application credentials.
- `MICROSOFT_REDIRECT_URI`: exactly `https://<your-render-host>/auth/microsoft/callback`.

Add that exact HTTPS callback URL as a **Web** redirect URI in Microsoft Entra ID. If using a custom domain, use it consistently for `APP_URL`, the Microsoft redirect URI, and Entra's registration. The Blueprint defaults to Render's Singapore region; choose a Render region close to the Supabase database region to reduce database latency.

The app's `/up` health endpoint does not verify database connectivity. After entering the environment values, confirm the deploy succeeds and check the Render logs and application sign-in for database connectivity. Never add a Supabase service-role key or database credentials to source control.

The Blueprint does not run migrations or seed data automatically. This app is configured to use the existing Supabase database: do not run `migrate:fresh` or seed demo data in production. Review the migration status and take a backup before applying any pending migrations. If required, run `php artisan migrate --force` once from a trusted environment connected to the production database.

Render's local container filesystem is ephemeral. Do not rely on it for durable user uploads; configure external object storage before introducing upload features. The initial deployment uses synchronous queues and file cache.

## Tests

`php artisan test` (uses in-memory SQLite; PostgreSQL-only constraints are skipped there).

## Layout
`app/Http/Controllers` (Auth, Student, Admin) · `app/Http/Middleware/RoleMiddleware.php` · `app/Models` · `database/migrations|seeders` · `resources/views/{layouts,auth,student,admin}` · `public/css/ferms.css` · `scripts/` · `tests/Feature`

## Not yet implemented

Calendar-grid schedule views and a screen for `facility_schedules` blocks, admin settings page, REST API, pagination.
