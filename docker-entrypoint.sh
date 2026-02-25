#!/bin/bash

echo "==> Caching config..."
php artisan config:cache 2>/dev/null || echo "WARNING: config:cache failed (APP_KEY missing?)"

echo "==> Caching routes..."
php artisan route:cache 2>/dev/null || echo "WARNING: route:cache failed"

echo "==> Caching views..."
php artisan view:cache 2>/dev/null || echo "WARNING: view:cache failed"

echo "==> Running migrations..."
php artisan migrate --force 2>&1 || echo "WARNING: migrate failed (DB not connected?)"

echo "==> Seeding database (only if users table is empty)..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "    DB is empty — running seeders..."
    php artisan db:seed --force 2>&1 || echo "WARNING: seed failed"
else
    echo "    DB already has ${USER_COUNT} users — skipping seed."
fi

PORT_NUM=$((${PORT:-8000}))
echo "==> Starting server on port ${PORT_NUM}..."
exec php -S 0.0.0.0:${PORT_NUM} -t public public/router.php
