# -----------------------------------------------------------------------------
# Production Dockerfile
#
# This image is intentionally minimal. We rely on the official WordPress image
# and avoid installing additional packages (e.g. via `apt update`) to ensure:
#   - Consistency across environments (e.g. staging vs production)
#   - Smaller, faster, more secure builds
# -----------------------------------------------------------------------------

# -----------------------------------------------------------------------------
# Build Stage - Build theme front-end assets
# -----------------------------------------------------------------------------
FROM node:20 AS builder
WORKDIR /app
COPY wp-content/themes/affinity/package*.json wp-content/themes/affinity/
RUN cd wp-content/themes/affinity && npm ci
COPY wp-content/themes/affinity wp-content/themes/affinity
RUN cd wp-content/themes/affinity && npm run build && rm -rf node_modules

# -----------------------------------------------------------------------------
# Composer Stage - Install PHP dependencies for plugins and theme
# -----------------------------------------------------------------------------

FROM composer:2 AS composer

# 1) Plugins
WORKDIR /app/plugins
COPY composer.json composer.lock .env auth.json ./
RUN composer install --no-dev --optimize-autoloader \
    && rm .env auth.json

# 2) Theme dependencies (autoload)
WORKDIR /app/theme-includes
COPY wp-content/themes/affinity/includes/composer.json wp-content/themes/affinity/includes/composer.lock ./
# Install into a dedicated vendor directory
RUN composer install --no-dev --optimize-autoloader \
    && echo "Vendor tree for theme includes:" \
    && ls -R vendor \
    && test -f vendor/autoload.php


# -----------------------------------------------------------------------------
# WP Core Stage - Pre-bake WordPress core into the image
# -----------------------------------------------------------------------------
FROM wordpress:latest AS wp-core

# -----------------------------------------------------------------------------
# Final Stage - Assemble WordPress container
# -----------------------------------------------------------------------------
FROM wordpress:latest

# 0) Copy pre-baked WP core so entrypoint skips runtime copy
COPY --from=wp-core /usr/src/wordpress/ /var/www/html/

# 1) Copy built theme (with includes)
COPY --from=builder /app/wp-content/themes/affinity /var/www/html/wp-content/themes/affinity
# Merge in composer-installed includes/vendor
COPY --from=composer /app/theme-includes/vendor /var/www/html/wp-content/themes/affinity/includes/vendor

# 2) Copy plugins
COPY --from=composer /app/plugins/wp-content/plugins /var/www/html/wp-content/plugins/
#COPY wp-content/plugins /var/www/html/wp-content/plugins/

# 3) Ownership
# RUN chown -R www-data:www-data /var/www/html/wp-content

# 4) Add custom PHP settings
COPY local/uploads.ini /usr/local/etc/php/conf.d/uploads.ini