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
COPY wp-content/themes/ wp-content/themes/
RUN for theme in wp-content/themes/*/; do \
      theme_name=$(basename "${theme}"); \
      if echo "${theme_name}" | grep -q "^twenty"; then \
        echo "Skipping default theme: ${theme_name}"; \
        continue; \
      fi; \
      if [ -f "${theme}package.json" ]; then \
        echo "Building theme: ${theme}" && \
        cd "${theme}" && \
        npm ci && \
        npm run build && \
        rm -rf node_modules && \
        cd /app; \
      fi; \
    done

# -----------------------------------------------------------------------------
# Composer Stage - Install PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer
WORKDIR /app/plugins
COPY composer.json composer.lock .env auth.json ./
RUN composer install --no-dev --optimize-autoloader \
    && rm .env auth.json

# -----------------------------------------------------------------------------
# Final Stage - Assemble WordPress container
# -----------------------------------------------------------------------------
FROM wordpress:latest

# Install phpredis extension and WP-CLI
RUN apt-get update \
    && apt-get install -y --no-install-recommends libz-dev libssl-dev git unzip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=wordpress:cli /usr/local/bin/wp /usr/local/bin/wp

# Copy WordPress core files
COPY --from=wordpress:latest /usr/src/wordpress/ /var/www/html/

# Copy built themes and plugins
COPY --chown=www-data:www-data --from=builder /app/wp-content/themes/ /var/www/html/wp-content/themes/
COPY --chown=www-data:www-data --from=composer /app/plugins/wp-content/plugins /var/www/html/wp-content/plugins/
COPY --chown=www-data:www-data wp-content/plugins /var/www/html/wp-content/plugins/
COPY --chown=www-data:www-data wp-config.php /var/www/html/wp-config.php

# Add custom PHP settings
COPY build/uploads.ini /usr/local/etc/php/conf.d/uploads.ini