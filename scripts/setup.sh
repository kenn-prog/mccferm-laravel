#!/usr/bin/env bash
# One-step local setup for MCC-FERMS. Usage: bash scripts/setup.sh
set -euo pipefail
cd "$(dirname "$0")/.."
php -r 'foreach (["pdo_pgsql","mbstring","xml","curl","zip","intl","bcmath"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "Missing PHP extension: $e\n"); exit(1); } }'
composer install --no-interaction --prefer-dist
test -f .env || cp .env.example .env
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force
npm install && npm run build
echo "Edit .env with your Supabase credentials, then run: php artisan migrate --seed && php artisan serve"
