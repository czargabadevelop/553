# baseplate-react

A Laravel starter kit ("baseplate") built with **Inertia.js + React 19** and **Tailwind CSS v4**. It ships with authentication, a dashboard, and a full CRUD example to build on.

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
