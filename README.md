# Tomato Lane

A personal kanban board with a built-in pomodoro timer, time tracking, breaks
and statistics. Rebuilt from scratch to replace the Filament-based
`kanbanflow-dashboard`, which stays read-only as a reference for business rules
and tests.

## Stack

- Laravel 13, PHP 8.5
- Inertia 3 + Vue 3 + TypeScript, Tailwind CSS 4 (Laravel Vue starter kit)
- PostgreSQL 16 (local: Docker, production: `caws`)
- Pest 5, Pint, PHPStan, vue-tsc

## Local setup

The app is served by Herd at <http://tomato-lane.test> (`~/Codes` is a parked path).

```bash
docker compose up -d        # Postgres 16 on 127.0.0.1:5432
composer setup              # install, .env, key, migrate, build assets
composer dev                # optional: Vite dev server, queue, logs
```

`docker compose up` creates two databases with the same encoding and locale as
production (`UTF8`, `C.UTF-8`):

| Database           | Used by                        |
| ------------------ | ------------------------------ |
| `tomato_lane`      | the app (`.env`)               |
| `tomato_lane_test` | the Pest suite (`phpunit.xml`) |

The local credentials (`tomato_lane` / `tomato_lane`) exist only inside the
container, which listens on loopback only.

To start over with an empty database:

```bash
docker compose down -v && docker compose up -d && php artisan migrate
```

## Time zones

PHP, Laravel and Postgres all run in UTC, and timestamps are stored as
`timestamptz`. `APP_USER_TIMEZONE` (default `America/Vancouver`) is used only
for calendar boundaries the user sees, such as which cards the Done column
groups under "Today".

## Checks

```bash
composer test         # Pint, PHPStan, Pest (against Postgres)
npm test              # frontend unit tests (Vitest via vite-plus)
npm run check         # frontend format + lint
npm run types:check
```
