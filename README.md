# Pepito — Philippine 13-Card Chinese Poker

A four-player **Pepito** platform (the Philippine variant of 13-card Chinese poker, also known
as Pusoy) built on a Laravel starter kit with **Inertia.js + React 19** and **Tailwind CSS v4**.

## Stack

- PHP 8.3 / Laravel 13
- Inertia.js (`@inertiajs/react`) with React 19
- Tailwind CSS v4 (via `@tailwindcss/vite`) + Vite 7
- MySQL (database: `baseplate2` — see `.env`)
- Pest for testing, Laravel Pint for formatting
- UI extras already installed: ApexCharts, FullCalendar, jsvectormap, flatpickr, dropzone

## Getting started

```bash
composer setup   # composer install, .env, key:generate, migrate, npm install, npm run build
composer dev     # runs server + queue + vite concurrently
```

Or step by step:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

Then open http://localhost:8000.

## Docker (local)

The Compose stack runs Nginx (public HTTP entrypoint), PHP-FPM, a queue worker, the
scheduler, Laravel Reverb, MySQL 8 and Redis. Only Nginx and Reverb are reachable from the
host; MySQL and Redis stay on the internal network.

```bash
cp .env.docker.example .env          # then fill in the blank secrets
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"   # APP_KEY
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Open http://localhost:8000 and sign in as one of the four seeded test players:

| Username | Password | Notes |
|---|---|---|
| `player1` | `password` | Also a super administrator (admin dashboard) |
| `player2` | `password` | Player |
| `player3` | `password` | Player |
| `player4` | `password` | Player |

Use four separate browser profiles/incognito windows to seat all four players at one table.
Every seeded wallet starts with **PHP 1,000 in test credits** (never real money).

```bash
docker compose ps                         # service health
docker compose logs -f app queue reverb   # application logs
docker compose exec app php artisan migrate --force
php artisan test --compact                # run tests on the host (dev deps not in image)
```

Notes:

- `.env` is read by Compose for interpolation (database names, ports, Redis password) and by
  Laravel via `env_file`, so one file drives both.
- PHP dependencies are installed with `composer install --no-dev` in the image. The image does
  **not** include Pest/Pint/Boost; run the test suite on the host or in CI.
- Never bake `bootstrap/cache/*.php` into the image — `.dockerignore` excludes it because a
  locally generated package manifest can reference dev-only packages (`laravel/boost`).
- If you change `.env`, recreate the containers so cached config is rebuilt:
  `docker compose up -d --force-recreate`.

## Where to edit what

| What you want to change | Location |
| --- | --- |
| Routes / URLs | `routes/web.php` |
| Controllers | `app/Http/Controllers/` (`Auth/Login.php`, `Dashboard.php`, `CRUD.php`) |
| Models | `app/Models/` |
| Migrations & seeders | `database/migrations/`, `database/seeders/` |
| Eloquent factories | `database/factories/` |
| Pages (Inertia) | `resources/js/Pages/` — file name = page name (`Inertia::render('Crud/Index')` → `Pages/Crud/Index.jsx`) |
| Reusable UI components | `resources/js/Components/` |
| Page layouts | `resources/js/Layouts/` (`AuthenticatedLayout.jsx`, `GuestLayout.jsx`) |
| Theme / dark mode | `resources/js/lib/ThemeContext.jsx` |
| Chart configs | `resources/js/charts/options.js` |
| Global styles | `resources/css/style.css` (entry), `resources/css/app.css` |
| Images / static assets | `resources/assets/images/` |
| Blade shell (HTML root) | `resources/views/app.blade.php` |
| JS entry point | `resources/js/app.jsx` |
| Vite config / asset entry | `vite.config.js` |
| Middleware | `app/Http/Middleware/HandleInertiaRequests.php` (shared Inertia props) |
| App config | `config/`, `.env` |
| Tests | `tests/Feature/`, `tests/Unit/` |

### Routes at a glance

| URI | Name | Controller |
| --- | --- | --- |
| `GET /` | `login` | `Auth\Login@showLoginForm` (guest) |
| `POST /login` | `login.submit` | `Auth\Login@login` (guest) |
| `GET /dashboard` | `dashboard` | `Dashboard@index` (auth) |
| resource `/crud` | `crud.*` | `CRUD` resource (auth) |
| `POST /logout` | `logout` | `Auth\Login@logout` (auth) |
| fallback | — | renders `Errors/NotFound` (404) |

Adding a page = create a controller method returning `Inertia::render('MyPage')`, add the route in `routes/web.php`, and create `resources/js/Pages/MyPage.jsx`.

## Useful commands

```bash
composer dev        # serve + queue + vite (hot reload)
npm run dev         # vite only
npm run build       # production assets
composer test       # clear config + run tests
php artisan test --compact --filter=testName
vendor/bin/pint     # format PHP code
```

## Testing

Pest is set up in `tests/Pest.php`. Feature tests live in `tests/Feature/` (see `AuthAndCrudTest.php` for login + CRUD coverage).

## Platform documentation

This starter is the foundation for the **Pepito** platform — the Philippine variant of
Traditional 13-card Chinese Poker (also known as Pusoy).

| Document | Contents |
| --- | --- |
| `docs/ARCHITECTURE.md` | Repository inspection (stack, auth, layouts, components, schema, tests, gaps), conflicts and target structure |
| `docs/IMPLEMENTATION_PLAN.md` | Milestone-by-milestone delivery plan and acceptance criteria |
| `docs/DECISIONS.md` | Game/financial rules awaiting confirmation (foul settlement, conversion, fees, roles) |
| `docs/laravel-boost-guidelines.md` | The starter's original `AGENTS.md` (cannot coexist with `agents.md` on Windows) |
