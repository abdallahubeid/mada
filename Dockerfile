# syntax=docker/dockerfile:1
#
# ─────────────────────────────────────────────────────────────────────────────
# Mada — production image
#
# WHY DOCKER AND NOT A NATIVE RUNTIME
# Render has no PHP runtime. Its API rejects it outright:
#   "invalid runtime: php. valid runtimes are:
#    [docker, elixir, go, node, python, ruby, rust, image]"
# So the PHP toolchain is assembled here instead.
#
# WHAT DELIBERATELY DOES *NOT* HAPPEN AT BUILD TIME
# `php artisan config:cache` is NOT run here, even though it is a build-time
# step in most Laravel deployments. Render does not expose a service's
# environment variables to `docker build` — only to the running container. A
# config cache baked during the build would therefore freeze DB_HOST,
# DB_USERNAME and DB_PASSWORD as empty strings, and a cached config takes
# precedence over the environment at runtime. The app would boot cleanly and
# fail every single query.
#
# The three `artisan` cache commands run in the start command instead, where
# the environment actually exists. `composer install` and `npm run build` stay
# here, because neither reads service configuration.
# ─────────────────────────────────────────────────────────────────────────────


# ── Stage 1 · front-end assets ───────────────────────────────────────────────
# Split from the PHP stage so Node and its ~200 MB of dev dependencies never
# reach the final image; only the compiled output in public/build crosses over.
FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./

# `npm install` rather than `npm ci`: every front-end package here lives in
# devDependencies (vite, tailwind, laravel-vite-plugin), and the build needs
# them. `--omit=dev` would remove exactly what `vite build` requires.
RUN npm install

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# ── Stage 2 · PHP runtime ────────────────────────────────────────────────────
#
# 8.4, NOT the `"php": "^8.3"` that composer.json advertises. That constraint
# is the floor the application declares; the LOCKFILE is what actually gets
# installed, and it pins symfony/* 8.1.x, which requires php >= 8.4.1. Building
# on 8.3 fails at `composer install` with seventeen separate conflicts.
#
# composer.json is left alone deliberately: `^8.3` is not wrong, it is merely
# looser than the resolved set, and tightening it would be a source change made
# to satisfy a deployment rather than the other way round. If the lock is ever
# regenerated on an older PHP, this line is what has to move.
FROM php:8.4-cli-bookworm AS app

# `git` and `unzip` are for Composer's dist installs; the -dev headers are only
# needed while compiling the extensions below and are removed with the apt
# cache in the same layer.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        zip \
        intl \
        opcache \
    && apt-get purge -y --auto-remove libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# gd is intentionally absent. The only `Image` in this codebase is an Eloquent
# model (App\Models\Image, via HasImages) — nothing resizes or re-encodes, so
# the extension and its libpng/libjpeg build chain buy nothing.

# opcache is worth configuring rather than merely enabling: with `validate_
# timestamps=0` the source is never stat-ed on request, which is correct here
# because the code cannot change without replacing the container.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependency manifests first, so a change to application code does not
# invalidate the (slow) Composer layer.
COPY composer.json composer.lock ./

# `--no-scripts` because Laravel's post-install hook runs `artisan
# package:discover`, which boots the framework — and the application code has
# not been copied yet at this point in the layer order.
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --no-scripts \
        --no-progress

COPY . .

# Now that the app is present, finish what --no-scripts skipped.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi

COPY --from=assets /app/public/build ./public/build

# Laravel writes compiled views, logs and caches to these two trees at runtime.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data

# Documentation only — Render injects the real value as $PORT and the start
# command binds to it.
EXPOSE 8000

# Overridden by `dockerCommand` in render.yaml. Kept in sync with it so the
# image behaves the same when run outside Render.
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
