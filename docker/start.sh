#!/bin/sh
#
# Container start sequence.
#
# ─────────────────────────────────────────────────────────────────────────────
# WHY THIS IS A SCRIPT AND NOT A `&&` CHAIN IN render.yaml
#
# Render does not evaluate `dockerCommand` with a shell — it splits the string
# into argv and execs it. A chain written directly there fails in whichever way
# matches how it was quoted, and both failure modes are misleading:
#
#   dockerCommand: php artisan config:cache && php artisan route:cache && ...
#     → `&&` arrives as a positional argument to artisan:
#       "No arguments expected", exit 1
#
#   dockerCommand: sh -c "php artisan config:cache && ..."
#     → the quoted chain is split as well, and sh is handed a command name
#       that is the entire string:
#       sh: 1: php artisan config:cache && ...: not found, exit 127
#
# Naming a single executable sidesteps the question: one argv element, nothing
# to parse, and the sequencing lives in a file that can be read and reasoned
# about rather than in a config string.
# ─────────────────────────────────────────────────────────────────────────────

# Abort on the first failure. Without this, a failed migration would be
# followed by a web server that starts happily and serves 500s — the deploy
# would go green while the app is broken.
set -e

# ── Caches ───────────────────────────────────────────────────────────────────
# These run HERE, at container start, and deliberately not during the image
# build. Render exposes a service's environment variables to the running
# container but NOT to `docker build`, so a config cache produced at build time
# would freeze DB_HOST/DB_USERNAME/DB_PASSWORD as empty strings — and a cached
# config outranks the environment. The app would boot and fail every query.
echo "==> Caching configuration, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Schema ───────────────────────────────────────────────────────────────────
# `--force` because production migrations otherwise prompt for confirmation and
# there is no TTY here. Safe while the service runs a single instance; with
# more than one, concurrent starts would race and this needs to move to a
# release phase.
echo "==> Running migrations"
php artisan migrate --force

# ── Serve ────────────────────────────────────────────────────────────────────
# `exec` replaces the shell so the server becomes PID 1 and receives SIGTERM
# directly. Without it, Render's shutdown signal reaches the wrapper shell and
# the server is killed on the timeout instead of stopping cleanly.
#
# $PORT is assigned by Render per instance and is not stable across deploys, so
# it must be read at runtime rather than baked in.
echo "==> Starting server on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
