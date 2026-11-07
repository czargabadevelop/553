# Architecture & Repository Inspection — Milestone 1

Status: **complete** (Milestone 1). Date: 2026-11-07.

Source: `https://gitlab.com/czar.gaba/baseplate-react`, branch `main`, commit `60eefa0`
("Add Laravel Inertia React dashboard baseplate").

The starter has been imported unchanged into this repository so the poker platform is an
extension of it, not a replacement. Everything below is what was actually found in the code,
followed by the target structure for the poker platform.

> Note on instruction files: the starter ships `AGENTS.md` (Laravel Boost guidelines). Windows
> filesystems are case-insensitive, so it cannot coexist with the project specification file
> `agents.md`. The boost guidelines are preserved verbatim at
> [`docs/laravel-boost-guidelines.md`](laravel-boost-guidelines.md) and remain in force.

---

## 1. Current repository structure

```
app/
  Http/Controllers/
    Auth/Login.php          # login / logout (session auth)
    Controller.php
    CRUD.php                # user CRUD example
    Dashboard.php
  Http/Middleware/HandleInertiaRequests.php
  Models/User.php
  Providers/AppServiceProvider.php
bootstrap/app.php           # middleware, routing, health endpoint /up
config/                     # stock Laravel config (app, auth, cache, database, filesystems,
                            # logging, mail, queue, services, session)
database/
  factories/UserFactory.php
  migrations/               # users (+password_reset_tokens, sessions), cache, jobs
  seeders/DatabaseSeeder.php
resources/
  css/style.css, css/app.css
  js/                       # React app (see §3)
  views/app.blade.php       # Inertia root template
routes/
  web.php, console.php
tests/                      # Pest (see §8)
public/                     # built assets + TailAdmin image assets
vite.config.js, package.json, composer.json, phpunit.xml, boost.json
docs/                       # architecture + plan (this change)
```

There is no `routes/api.php`, no `app/Domain`, no `app/Policies`, no `app/Events`, no
`app/Jobs`, no `app/Enums`, no `Dockerfile`, no `docker-compose.yml`, and no CI pipeline
(`.gitlab-ci.yml` is absent; only `.github/skills/**` documentation exists).

## 2. Laravel version and dependencies

| Component | Version installed | Expected by spec |
|---|---|---|
| PHP (local dev) | 8.5.10 (XAMPP) | 8.3+ — OK, but production must be pinned |
| `laravel/framework` | ^13.0 | Laravel 13 — matches |
| `inertiajs/inertia-laravel` | ^3.3 | Inertia 3 — matches |
| `laravel/tinker` | ^3.0 | — |
| `pestphp/pest` | ^4.0 (4.7.8) | Pest — matches (spec/boost doc's "v3" is stale) |
| `laravel/pint` | ^1.24 | Pint — matches |
| `laravel/boost`, `laravel/pail`, `laravel/sail`, `nunomaduro/collision`, `mockery`, `faker` | dev tooling | — |

Node side: React `^19.3.0`, `@inertiajs/react` `^3.8.0`, Tailwind `^4.0.0` via
`@tailwindcss/vite`, Vite `^7.0.7`, `laravel-vite-plugin` `^2.0.0`, `@vitejs/plugin-react`
`^5.2.0`. UI libraries already present: ApexCharts, FullCalendar, jsvectormap, flatpickr,
dropzone, alpinejs (legacy, only used for a Blade-level concern).

Absent: Sanctum, Reverb, Redis client, any RBAC package, any money/decimal package, any JS
lint/formatter (no ESLint, no Prettier, no TypeScript).

## 3. React and Inertia structure

- Entry: `resources/js/app.jsx` boots Inertia via `createInertiaApp`, resolving pages with
  `import.meta.glob('./Pages/**/*.jsx', { eager: true })`. **Pages are `.jsx`, not `.tsx`**;
  there is no TypeScript anywhere.
- Root Blade template `resources/views/app.blade.php` loads
  `resources/css/style.css` + `resources/js/app.jsx`, injects a pre-paint dark-mode script,
  and wraps everything in `ThemeProvider` (`resources/js/lib/ThemeContext.jsx`, class-based
  `dark` variant persisted in `localStorage`).
- Pages: `Auth/Login`, `Dashboard`, `Crud/{Index,Create,Edit,Show}`, `Errors/NotFound`.
- Layouts: `AuthenticatedLayout` (sidebar + header + preloader + overlay) and `GuestLayout`.
- Shared props come from `HandleInertiaRequests::share()`: `auth.user` (id, name, username,
  email, role, profile_picture) and `flash.success`.
- Styling: Tailwind v4 CSS-first theme in `resources/css/style.css` — custom `brand`,
  `gray`, `success`, `error`, `warning` palettes, `text-theme-*` type scale, `shadow-theme-*`,
  `@utility` menu helpers. Any new UI must reuse these tokens (no ad-hoc palettes).

Adding a page = controller returns `Inertia::render('Folder/Page')` + a file at
`resources/js/Pages/Folder/Page.jsx` + a route.

## 4. Authentication implementation

- Custom, hand-written session auth — no Breeze/Jetstream/Fortify.
- `App\Http\Controllers\Auth\Login`: `GET /` (guest) renders `Auth/Login`, `POST /login`
  validates `username` + `password`, `Auth::attempt()`, `session()->regenerate()`, redirect to
  `dashboard`; `POST /logout` invalidates and regenerates the CSRF token.
- Login identifier is **username**, not email.
- Missing entirely: registration, forgot/reset password, email verification (the
  `MustVerifyEmail` import in `app/Models/User.php` is commented out), 2FA, rate limiting on
  login (`ThrottleRequests` is not applied), account status/suspension.
- `users` has a free-text `role` column defaulting to `user`; there is no permission system and
  no authorization anywhere (every `auth`-middleware route is reachable by every logged-in
  user, including `/crud` user management).
- `bootstrap/app.php`: `redirectUsersTo('/dashboard')`, `redirectGuestsTo('/')`,
  `HandleInertiaRequests` appended to the `web` group, health endpoint `/up`.

## 5. Dashboard and layouts

- `Dashboard` renders a TailAdmin-style eCommerce overview: metric cards, monthly sales /
  target / statistics charts, customer-demographic map, recent orders table. All data is
  hardcoded in the React components — the controller passes nothing.
- `Sidebar` nav is a static array (`DASHBOARD`, `CRUD USERS`) with active-state detection from
  `usePage().url`; `Header` hosts the theme toggle, notifications placeholder and user menu.
- `AuthenticatedLayout` is the shell every authenticated player page and admin page should
  inherit until a dedicated admin shell is added.

## 6. Reusable UI components

`resources/js/Components/`: `ApexChart`, `Breadcrumb`, `FlashMessage`, `Header`, `Icons`
(hand-written SVG icon set), `MetricGroup`, `Overlay`, `Preloader`, `RecentOrdersTable`,
`Sidebar`, `UserForm`, `WorldMap`. Charts config lives in `resources/js/charts/options.js`;
`useClickOutside` hook in `resources/js/lib/`.

These are TailAdmin-derived and dashboard-oriented. There is **no** card/playing-card,
table, modal, toast, badge, pagination, empty-state or form-field component kit yet — those
must be created for the poker UI (see plan, Milestone 6).

## 7. Database migrations and models

| Migration | Tables |
|---|---|
| `0001_01_01_000000_create_users_table` | `users` (id, name, nullable unique `username`, unique `email`, `profile_picture`, `email_verified_at`, `password`, `role` string default `user`, `remember_token`, timestamps), `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |

Models: `App\Models\User` only — `HasFactory`, `Notifiable`, `$fillable` = name, username,
email, profile_picture, password, role; `password` cast `hashed`; `email_verified_at` datetime.

Factories: `UserFactory` (password `password`, verified by default, `unverified()` state) —
no `username` in `definition()`, so tests must set it explicitly.
Seeder (current): `DatabaseSeeder` creates exactly four test players
(`player1..4` / `password`) with PHP 1,000 test-credit wallets and prunes any other
accounts. `player1` also holds the super administrator role.

No financial, room, game, audit or settings tables exist. `DB_CONNECTION=mysql`,
`DB_DATABASE=baseplate2` in `.env.example`; tests use SQLite `:memory:`.

## 8. Testing setup

- Pest 4 + `pest-plugin-laravel`, PHPUnit 12 under the hood; suites `Unit`
  (`tests/Unit`) and `Feature` (`tests/Feature`); `tests/Pest.php` binds
  `Tests\TestCase` to `Feature` only and **does not** apply `RefreshDatabase` globally —
  test files opt in with `uses(RefreshDatabase::class)` (the existing feature test does).
- `phpunit.xml` forces testing env: sqlite `:memory:`, array cache/session, sync queue,
  `BROADCAST_CONNECTION=null`, fixed APP_KEY.
- Coverage today: 1 unit test, 12 feature tests (`AuthAndCrudTest`) covering guest redirects,
  login screen, login success/failure, dashboard, CRUD index/create/edit/show/store/update/
  delete, validation errors, logout and the Inertia 404 fallback (92 assertions). Inertia
  pages are asserted with `Inertia\Testing\AssertableInertia` and `$this->withoutVite()`.
- No architecture tests, no factories beyond `UserFactory`, no browser tests.

## 9. Docker configuration

Added for Milestone 11 groundwork (not present in the starter):

- `Dockerfile` — multi-stage: `assets` (Vite build), `vendor` (Composer `--no-dev`),
  `app` (PHP 8.4-FPM + bcmath/intl/opcache/pdo_mysql/pcntl/redis) and `web`
  (Nginx serving `public/` and proxying PHP to `app:9000`).
- `docker-compose.yml` — `web`, `app`, `queue`, `scheduler`, `reverb`, `mysql:8.4`,
  `redis:7-alpine` with health checks and named volumes. MySQL/Redis are never published.
- `docker/entrypoint.sh` — caches config/routes/views per service at start (migrations run
  explicitly, never on boot).
- `docker/nginx/default.conf`, `docker/php/opcache.ini`.
- `.env.docker.example` (local Compose) and `.env.production.example` (VPS) — secrets are
  supplied per environment; `.env` is never committed.
- `.dockerignore` excludes `bootstrap/cache/*.php` so a developer-machine package manifest
  (which can include dev-only `laravel/boost`) never ships in the runtime image.
- `laravel/reverb` is a **runtime** dependency (moved out of `require-dev`) because the
  `reverb` service and the `reverb` broadcast driver are needed in production.

`docker compose up -d --build` now serves the app at `http://localhost:8000`; the host
aaPanel Nginx can still proxy directly to `app:9000` in production.

## 10. Missing packages and integrations

| Need (spec §) | Missing piece |
|---|---|
| API auth, §3 | `laravel/sanctum` + token/session strategy for `/api/v1` |
| Real-time, §11 | `laravel/reverb` (now a runtime dependency), a Redis client (`phpredis` ext), private-channel authorization |
| Roles/permissions, §13 | `spatie/laravel-permission` **or** first-party policies + roles tables (decision pending) |
| Money/decimals, §7 | no decimal library — plan is integer minor units + a `Money` value object (no dependency) |
| Jobs/queues, §11 | driver config only (database driver today); Redis not configured as queue/cache |
| Reports/export, §12 | CSV export can be streamed natively; no package needed |
| File uploads (deposits/KYC), §8/§12 | `Storage` is configured; needs private disk + signed URLs |
| Frontend quality gates | no ESLint/Prettier/TypeScript; no `npm test` script |
| CI/CD, §18 | no `.gitlab-ci.yml` |
| Rate limiting, §14 | no custom limiters |
| Feature flags, §14 | no implementation |

## 11. Potential conflicts with the proposed architecture

1. **SQLite in tests vs row locking.** `lockForUpdate()` is a no-op on SQLite. Financial
   concurrency tests (§19) must run against MySQL; keep SQLite for the fast unit/feature
   suites and add a MySQL-backed suite (or CI job) for locking/idempotency tests.
2. **`users.role` string vs RBAC.** The column and the Inertia `auth.user.role` prop must be
   replaced by (or derived from) the real role/permission model, without breaking login.
3. **No admin boundary.** `/crud` is open to any authenticated user; a `role` check middleware,
   admin layout and policies are prerequisites for §12.
4. **`GET /` is the login screen** and a web-scoped `Route::fallback` renders the Inertia 404.
   `routes/api.php` must be registered in `bootstrap/app.php` and needs its own JSON 404 so
   API clients never receive an Inertia HTML payload.
5. **No registration / password reset / email verification**, all required by §6. They are
   additive controllers + pages; the existing username login must keep working.
6. **Session-auth assumptions.** Sanctum for a future Next.js/Capacitor client means
   token-based auth must be enabled without weakening CSRF protection on the web app.
7. **Static dashboard data.** `Dashboard.jsx` and the metric/chart components are hardcoded
   demo content; the player dashboard has different metrics and must not inherit fake numbers.
8. **Bundle size.** A single 1.56 MB JS chunk (477 kB gzip) already triggers Vite's warning;
   the table/drag-and-drop UI will grow it, so route-level code splitting is needed.
9. **Design mismatch.** The starter is a light/dark TailAdmin dashboard; §16 wants a
   professional (mostly dark) card-game interface. Reuse the tokens/components, do not replace
   the design system, and add game-specific surfaces on top.
10. **Fat controllers.** `CRUD.php` mixes validation, file storage and persistence. New
    features must keep controllers thin and push rules into services (§3, §21.5).
11. **Case-insensitive instruction files.** `AGENTS.md` vs `agents.md` cannot coexist on
    Windows (handled as described at the top).
12. **Local PHP 8.5** is newer than the 8.3 baseline in `composer.json`/the spec; production
    images must pin 8.3 or 8.4 explicitly.

## 12. Recommended implementation structure

Additive only — the existing folders, app shell, auth flow and Tailwind theme stay.

```
app/
  Domain/                        # business logic, framework-light, unit-testable
    Game/
      Card.php Deck.php Hand.php HandEvaluator.php FoulDetector.php
      Scoring/ScoreAggregator.php SettlementCalculator.php
      GameEngine.php
    Wallet/
      Money.php                  # integer minor units, no floats
      LedgerService.php ReservationService.php SettlementService.php
      DepositService.php WithdrawalService.php
      PlatformFeeService.php
    Rooms/RoomService.php SeatingService.php MatchmakingService.php
    Settings/SettingsService.php FeatureFlagService.php
    Audit/AuditLogger.php
  Enums/                         # RoomStatus, GameStatus, HandPosition, LedgerAccount, ...
  Events/ + Broadcasts/          # real-time events (private channels)
  Http/Controllers/
    Player/...                   # Inertia player pages
    Admin/...                    # Inertia admin pages
    Api/V1/...                   # JSON API, thin, delegating to App\Domain
  Http/Requests/  Resources/  Policies/  Middleware/
  Models/                        # Eloquent only: persistence + relationships + casts
  Support/                       # cross-cutting helpers
database/migrations|factories|seeders
resources/js/
  Pages/{Player,Admin,Auth,Errors}/...
  Layouts/{PlayerLayout,AdminLayout,AuthenticatedLayout,GuestLayout}.jsx
  Components/{ui,game,wallet,admin}/...
  lib/, charts/, hooks/
routes/{web.php,api.php,console.php,channels.php}
docs/
```

Rules that follow from this: controllers never compute scores or balances; every monetary
value is an integer in minor units (`int`, `bigint` columns) or a `Money` object; all game
randomness is server-side (`random_int`/`Random\Randomizer`); private cards are only ever
serialized for the owning player; every financial mutation runs inside
`DB::transaction()` with `lockForUpdate()` and an idempotency key.

## 13. Verified baseline

Run on the imported starter before any modification:

| Check | Command | Result |
|---|---|---|
| PHP dependencies | `composer install` | OK (130 packages) |
| Node dependencies | `npm install` | OK (151 packages) |
| Test suite | `php artisan test` | 13 tests / 92 assertions pass (warnings only while `.env` was missing) |
| Frontend build | `npm run build` | OK — `public/build/assets/app-*.js` 1.56 MB (477 kB gzip), chunk-size warning |
| Local dev DB | `.env.example` → MySQL `baseplate2`; tests → SQLite `:memory:` | OK |

Milestone-by-milestone delivery plan: [`docs/IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md).
Game/financial rules that need an explicit decision before coding:
[`docs/DECISIONS.md`](DECISIONS.md).
