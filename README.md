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

## Tests

`php artisan test` (uses in-memory SQLite; PostgreSQL-only constraints are skipped there).

## Layout
`app/Http/Controllers` (Auth, Student, Admin) · `app/Http/Middleware/RoleMiddleware.php` · `app/Models` · `database/migrations|seeders` · `resources/views/{layouts,auth,student,admin}` · `public/css/ferms.css` · `scripts/` · `tests/Feature`

## Not yet implemented

Calendar-grid schedule views and a screen for `facility_schedules` blocks, admin settings page, REST API, pagination.
