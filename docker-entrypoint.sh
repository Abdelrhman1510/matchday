#!/bin/bash

echo "==> Caching config..."
php artisan config:cache 2>/dev/null || echo "WARNING: config:cache failed (APP_KEY missing?)"

echo "==> Caching routes..."
php artisan route:cache 2>/dev/null || echo "WARNING: route:cache failed"

echo "==> Caching views..."
php artisan view:cache 2>/dev/null || echo "WARNING: view:cache failed"

echo "==> Running migrations..."
php artisan migrate --force 2>&1 || echo "WARNING: migrate failed (DB not connected?)"

echo "==> Seeding database..."
php artisan db:seed --force 2>/dev/null || true

PORT_NUM=$((${PORT:-8000}))
echo "==> Starting server on port ${PORT_NUM}..."
exec php -S 0.0.0.0:${PORT_NUM} -t public public/index.php
