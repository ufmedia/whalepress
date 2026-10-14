# syntax=docker/dockerfile:1
# -----------------------------------------------------------------------------
# WhalePress image
#
# Targets:
#   dev         Local development (Xdebug, Composer). Used by docker-compose.yml,
#               which bind-mounts wp-content, wp-config.php and vendor.
#   production  Immutable image: core, theme, plugins and vendor baked in.
#
# Build args:
#   THEME_NAME   Theme directory under wp-content/themes (default: whalepress).
#   APP_VERSION  Release version, stamped into the theme and exposed as an env var.
#
# Private Composer repositories: pass credentials as a BuildKit secret, e.g.
#   docker build --secret id=composer_auth,src=auth.json .
# -----------------------------------------------------------------------------

ARG WORDPRESS_IMAGE=wordpress:7.1-php8.4-apache
ARG NODE_IMAGE=node:24-slim
ARG COMPOSER_IMAGE=composer:2

# -----------------------------------------------------------------------------
# Theme assets
# -----------------------------------------------------------------------------
FROM ${NODE_IMAGE} AS assets
ARG THEME_NAME=whalepress
WORKDIR /theme

# Install dependencies first so this layer is cached until the lockfile changes.
COPY wp-content/themes/${THEME_NAME}/package.json wp-content/themes/${THEME_NAME}/package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund

COPY wp-content/themes/${THEME_NAME}/ ./
RUN npm run build \
    && rm -rf node_modules hot assets/css assets/js scripts package.json package-lock.json *.config.js

# -----------------------------------------------------------------------------
# PHP dependencies (Timber and wpackagist plugins)
# -----------------------------------------------------------------------------
FROM ${COMPOSER_IMAGE} AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache \
    --mount=type=secret,id=composer_auth,env=COMPOSER_AUTH \
    composer install --no-dev --no-interaction --no-progress --no-autoloader --no-scripts --prefer-dist

# The autoloader maps the theme's PSR-4 namespace, so generate it after copying src/.
ARG THEME_NAME=whalepress
COPY wp-content/themes/${THEME_NAME}/src wp-content/themes/${THEME_NAME}/src
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

# -----------------------------------------------------------------------------
# Base runtime: WordPress + PHP extensions + WP-CLI
# -----------------------------------------------------------------------------
FROM ${WORDPRESS_IMAGE} AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions redis

COPY --from=wordpress:cli-php8.4 /usr/local/bin/wp /usr/local/bin/wp
ENV WP_CLI_CACHE_DIR=/tmp/wp-cli-cache
COPY build/php.ini /usr/local/etc/php/conf.d/zz-whalepress.ini
COPY build/apache.conf /etc/apache2/conf-enabled/zz-whalepress.conf

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/wp-includes/images/blank.gif") === false ? 1 : 0);'

# -----------------------------------------------------------------------------
# Development
# -----------------------------------------------------------------------------
FROM base AS dev

RUN install-php-extensions xdebug \
    && apt-get update \
    && apt-get install -y --no-install-recommends mariadb-client \
    && rm -rf /var/lib/apt/lists/*
COPY build/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# -----------------------------------------------------------------------------
# Production
# -----------------------------------------------------------------------------
FROM base AS production
ARG THEME_NAME=whalepress
ARG APP_VERSION=0.0.0-development
ENV APP_VERSION=${APP_VERSION}

# Bake core into the image rather than letting the entrypoint copy it at runtime.
RUN cp -a /usr/src/wordpress/. /var/www/html/ \
    && rm -rf /var/www/html/wp-content/plugins/akismet /var/www/html/wp-content/plugins/hello.php

COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor
COPY --chown=www-data:www-data --from=vendor /app/wp-content/plugins /var/www/html/wp-content/plugins
COPY --chown=www-data:www-data wp-content/plugins /var/www/html/wp-content/plugins
COPY --chown=www-data:www-data wp-content/mu-plugins /var/www/html/wp-content/mu-plugins
COPY --chown=www-data:www-data --from=assets /theme /var/www/html/wp-content/themes/${THEME_NAME}
COPY --chown=www-data:www-data wp-config.php /var/www/html/wp-config.php

# Object cache drop-in from the Redis Object Cache plugin. It stays inactive
# while REDIS_HOST is empty (see WP_REDIS_DISABLED in wp-config.php).
RUN cp /var/www/html/wp-content/plugins/redis-cache/includes/object-cache.php /var/www/html/wp-content/object-cache.php \
    && chown www-data:www-data /var/www/html/wp-content/object-cache.php \
    && sed -i "s/^Version: .*/Version: ${APP_VERSION}/" /var/www/html/wp-content/themes/${THEME_NAME}/style.css
