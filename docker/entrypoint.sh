#!/bin/sh
set -e

# Render assigns the port at runtime, so the nginx config is templated.
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# A missing APP_KEY produces confusing decryption errors much later, so fail
# loudly here instead.
if [ -z "${APP_KEY}" ]; then
  echo "FATAL: APP_KEY is not set. Generate one with 'php artisan key:generate --show'." >&2
  exit 1
fi

echo "==> Caching configuration"
php artisan config:cache
php artisan route:cache

# Views are Blade-only; the API ships one welcome page, but caching is cheap.
php artisan view:cache || true

# Migrations run on boot because Render's free tier has no release phase.
# Safe here only because the free tier runs a single instance — with several,
# concurrent boots could race and this should move to a job that runs once.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "==> Running migrations"
  php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  echo "==> Seeding (roles and admin only in production)"
  php artisan db:seed --force
fi

echo "==> Starting nginx and php-fpm on port ${PORT}"
exec supervisord -c /etc/supervisord.conf
