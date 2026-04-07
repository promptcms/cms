#
# PromptCMS — Multi-stage Docker build with FrankenPHP
#

# ---- Stage 1: Composer dependencies ----
FROM composer:2 AS composer-build

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev


# ---- Stage 2: Node build (needs vendor/ for Filament theme CSS) ----
FROM node:22-alpine AS node-build

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Filament theme CSS imports from vendor/
COPY --from=composer-build /app/vendor/filament ./vendor/filament
COPY --from=composer-build /app/app/Filament ./app/Filament
RUN npm run build


# ---- Stage 3: Production image with FrankenPHP ----
FROM dunglas/frankenphp:php8.4-alpine

# Install PHP extensions needed by Laravel + media library
RUN install-php-extensions \
    gd \
    intl \
    opcache \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    zip

# Set working directory
WORKDIR /app

# Copy application code from composer stage
COPY --from=composer-build /app /app

# Copy built frontend assets from node stage
COPY --from=node-build /app/public/build /app/public/build

# Create necessary directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/cms \
    storage/app/public \
    database \
    plugins \
    public/css \
&& ln -sf /app/storage/app/public /app/public/storage

# Create SQLite database if it doesn't exist
RUN touch database/database.sqlite

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/database /app/plugins /app/public/css /app/bootstrap/cache

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-promptcms.ini"

# Caddy configuration
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
