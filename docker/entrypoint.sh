#!/bin/sh
set -e

echo "=== PromptCMS starting ==="

# Normalize APP_KEY: Coolify's SERVICE_BASE64_32_* generates a raw base64 string
# without the "base64:" prefix that Laravel requires — add it if missing.
if [ -n "$APP_KEY" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    export APP_KEY="base64:$APP_KEY"
fi

if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    export APP_KEY=$(php artisan key:generate --show --no-interaction --force)
fi

# Create SQLite database if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    touch /app/database/database.sqlite
    chown www-data:www-data /app/database/database.sqlite
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Download Tailwind standalone CLI if not present
if [ ! -f /app/storage/cms/tailwindcss ]; then
    echo "Downloading Tailwind CLI..."
    php artisan cms:download-tailwind 2>/dev/null || true
fi

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Publish Filament assets
php artisan filament:upgrade --no-interaction 2>/dev/null || true

# Compile CMS CSS (only if already installed)
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" != "0" ]; then
    echo "Compiling CMS CSS..."
    php artisan cms:compile-css 2>/dev/null || true
else
    echo "Fresh install — open the app in your browser to run the installer."
fi

# Fix permissions
chown -R www-data:www-data /app/storage /app/database /app/plugins /app/public/css /app/bootstrap/cache

echo "=== PromptCMS ready ==="

# Execute CMD
exec "$@"
