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

# `git` and `unzip` are for Composer's dist installs.
#
# The -dev packages are deliberately NOT purged afterwards. An earlier revision
# ran `apt-get purge --auto-remove libzip-dev libicu-dev` to save a few dozen
# megabytes, and it took the RUNTIME shared objects with the headers:
#
#   Warning: PHP Startup: Unable to load dynamic library 'zip'
#   (libzip.so.4: cannot open shared object file: No such file or directory)
#
# The extensions compiled fine and then had nothing to link against at boot.
# Re-adding just the runtime libs means hardcoding version-suffixed package
# names (libzip4, libicu72) that change with the Debian release, which trades a
# silent runtime break for a silent build break. Keeping the headers is the
# honest cost.
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

# `chmod +x` rather than relying on the committed mode bit: this repository is
# developed on Windows, where git records 100644 for shell scripts, and the
# container would fail with "permission denied" on a file that looks correct in
# the tree.
RUN chmod +x docker/start.sh

# Laravel writes compiled views, logs and caches to these two trees at runtime.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# ─────────────────────────────────────────────────────────────────────────────
# UPLOAD DIRECTORIES — the cause of the 500 on avatar upload.
#
# The `custom` disk roots at public_path('') (config/filesystems.php), so every
# admin and tenant upload lands somewhere under public/. Everything COPYed
# above is owned by root, and the process runs as www-data — so the first
# upload of any kind died trying to create its directory:
#
#   League\Flysystem\UnableToCreateDirectory:
#   Unable to create a directory at /var/www/html/public/user/avatar
#
# That surfaces as an unhandled 500 rather than a handled failure, because
# Laravel's `'throw' => false` on the disk only swallows UnableToWriteFile —
# UnableToCreateDirectory is a different class and escapes. Verified by
# reproduction against an unwritable root.
#
# ONLY THE UPLOAD SUBTREES ARE HANDED OVER, never public/ as a whole. public/
# also holds index.php and the compiled Vite bundle; a web process able to
# rewrite its own entrypoint and assets is a far worse problem than a failed
# avatar upload.
#
# All eleven are created here, not just user/avatar — they are the same bug and
# fixing one would leave ten identical 500s waiting behind the other forms.
# ─────────────────────────────────────────────────────────────────────────────
RUN set -eux; \
    for d in \
        user/avatar \
        testimonial/avatar \
        tenant/logo \
        uploads/settings \
        expenses \
        aifeature/icon \
        feature/icon \
        module/icon \
        offering/icon \
        problem/icon \
        solution/icon \
    ; do \
        mkdir -p "public/$d"; \
    done; \
    chown -R www-data:www-data \
        public/user public/testimonial public/tenant public/uploads \
        public/expenses public/aifeature public/feature public/module \
        public/offering public/problem public/solution

USER www-data

# Documentation only — Render injects the real value as $PORT and the start
# script binds to it.
EXPOSE 8000

# Exec form, one argv element. Render splits `dockerCommand` into argv without
# a shell, so anything with `&&` or quoting in it is misparsed; naming a single
# executable is what makes the two paths (local `docker run` and Render)
# behave identically. See docker/start.sh for the reasoning in full.
CMD ["./docker/start.sh"]
