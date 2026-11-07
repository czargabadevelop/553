# Implementation Plan

Milestones are delivered in order; each one ends with passing automated tests before the next
begins (`agents.md` §20, §21.25). Real-money features stay disabled behind feature flags until
the approvals in `agents.md` §14 are confirmed.

Legend: **done** · **next** · pending

---

## Milestone 1 — Repository inspection and foundation — **done**

- Starter imported unchanged into this repository (branch `main`, commit `60eefa0`).
- Verified baseline: `composer install`, `npm install`, `php artisan test` (13 tests pass),
  `npm run build`.
- Documented [`ARCHITECTURE.md`](ARCHITECTURE.md) (stack, auth, layouts, components, schema,
  tests, gaps, conflicts, target structure) and [`DECISIONS.md`](DECISIONS.md).
- Preserved the starter's Laravel Boost guidelines at `docs/laravel-boost-guidelines.md`.

Exit criteria: baseline builds and tests pass, architecture and open decisions documented.
**Met.**

## Milestone 2 — Database and backend foundation — **next**

1. Install `laravel/sanctum` (+ `laravel/reverb` and a Redis client when Milestone 8 starts).
2. Register `routes/api.php` under `/api/v1` in `bootstrap/app.php` with a JSON 404 and
   `auth:sanctum` guard; keep the web session auth untouched.
3. Roles/permissions: `roles`, `permissions`, pivot tables (or spatie equivalent), seeded with
   the five roles in §13; `role` string remains only as a compatibility mirror.
4. Schema (additive migrations, FKs + indexes + unique constraints): player profiles and
   account verification, rooms and room seats, games/game_players/game_hands/
   game_hand_results, wallets/wallet_ledger_entries/wallet_reservations, deposits,
   withdrawals, payment/payout methods, system/game/room/fee settings, feature flags,
   audit log tables.
5. `App\Domain\{Game,Wallet,Rooms,Settings,Audit}` skeletons + `App\Enums` (room/game status,
   hand position, ledger account, transaction type).
6. `AuditLogger` writing administrative and security events; `SettingsService` and
   `FeatureFlagService` reading typed config from the DB with cached values.
7. Factories + seeders for every new model; money columns are `bigint` minor units.

Acceptance: migrations run clean on MySQL and SQLite, all models have factories and
relationships, settings/feature flags resolve with safe (money-off) defaults, audit logging
works, existing tests still pass.

## Milestone 3 — Player application

Registration, login/out, forgot/reset password, email verification, account status; player
dashboard (balance, reserved, recent transactions/games, stats, rooms, announcements);
profile (avatar, password change, verification status, responsible gaming); wallet screens
(available/reserved, deposits, withdrawals, transactions, winnings/losses/fees); lobby listing
rooms with entry amount, platform fee and total reservation disclosed.

Acceptance: feature tests for register/verify/reset, dashboard props, profile update and
wallet rendering; guests still redirected; username login unchanged.

## Milestone 4 — Game engine

`Card`, `Deck` (52 cards, `Random\Randomizer` with CSPRNG), dealing (13 unique cards ×4,
no duplicates, subset of deck), `HandEvaluator` (5-card categories 1–10 with kickers;
3-card trips/pair/high card only), `FoulDetector` (back ≥ middle ≥ front), pairwise comparison
(6 pairings × 3 hands = 18 comparisons), scoring (+1/0/−1 per hand, zero-sum before fees),
game result assembly and persistence.

Acceptance: deterministic unit tests with fixed card fixtures for every category, tie-breaks,
wheels, foul detection, score aggregation zero-sum, and PHP conversion; no float arithmetic.
Requires the foul-settlement decision in `DECISIONS.md` (D1).

## Milestone 5 — Rooms and matchmaking

Room CRUD (admin), entry configuration, seating, ready-up, four-player start gating,
entry reservation (max loss + fee), leave-before-start, cancel/incomplete-table handling,
matchmaking timeouts, lifecycle `Waiting → Filling → Ready → In Progress → Completed/Cancelled`
(enforced by a state machine, not free-form updates).

Acceptance: tests for seating rules, insufficient funds, full-table start, leave/cancel,
and that a game cannot start without four authorized, reserved players.

## Milestone 6 — Interactive poker table

Four-seat table UI, private card delivery (own cards only, never in shared props/events),
drag-and-drop + touch arrangement (5/5/3 with counts and client-side hints), submit with
server validation and foul messaging, timers/connection status, results screen with all four
hands, per-hand comparisons, game points, PHP-equivalent, fees and final settlement.

Acceptance: Inertia/feature tests prove one player cannot see another's cards pre-reveal;
submission validation and re-submission blocking are server-enforced.

## Milestone 7 — Wallet and financial system

Double-entry ledger (immutable entries, compensating corrections, unique references),
reservations with `lockForUpdate()` inside `DB::transaction()`, idempotent settlement keyed on
game, deposits (pending → under review → approved/rejected/cancelled with proof review),
withdrawals (reserve on request, release on reject/cancel, complete only after confirmed
payout), configurable platform fee (disabled by default), reconciliation report, test credits
clearly labelled as non-real.

Acceptance: ledger balances per transaction, duplicate settlement/charge prevention, concurrent
withdrawal test on MySQL, reconciliation to zero drift.

## Milestone 8 — Real-time communication

Reverb + Redis, private channels authorized per player/game, events for join/leave/ready/
table-filled/started/dealt/submitted/evaluated/completed/settled/disconnect/reconnect/timers,
reconnection returning only authorized state, timeout policies.

Acceptance: channel authorization tests, event payload tests proving no foreign private cards,
reconnect restores state and blocks card changes after submission.

## Milestone 9 — Administrative dashboard

Separate admin layout/navigation; overview metrics; player management (search, suspend,
reactivate, history); KYC review; room management; game monitoring (hands, results, fouls,
audit trail, no silent edits); deposit/withdrawal queues with separated approve vs payout
permissions; ledger browser with filters + CSV export; reports; settings with change audit log.

Acceptance: every admin route is policy-protected, non-admin access is 403, every mutation is
audited, completed games/ledger entries are read-only.

## Milestone 10 — Security, compliance and testing

Authorization matrix tests (player/admin/finance/game/support), rate limiters on auth and
financial endpoints, input validation review, private uploads with signed URLs, security
headers, API tests for `/api/v1`, feature-flag enforcement tests (backend, not just UI),
backup/restore procedure, and a written security review.

## Milestone 11 — Docker and production deployment

Multi-stage `Dockerfile` (php-fpm app image reused by queue, scheduler, Reverb), 
`docker-compose.yml` (MySQL 8, Redis, app, queue, scheduler, Reverb, health checks, persistent
volumes), production `.env.example`, aaPanel/Nginx reverse proxy + WebSocket upgrade,
Cloudflare TLS/headers, `.gitlab-ci.yml` (install → PHP syntax → Pint → Pest → Vite build →
image build → SSH deploy with health check + rollback), monitoring/logging.

Production readiness gate: real-money flags remain off until PAGCOR/legal/payment approvals are
recorded.

---

## Cross-cutting rules

- Controllers thin; business rules in `App\Domain`; models are persistence only.
- No floating-point money. `bigint` minor units, `Money` value object.
- Every financial mutation: transaction + row lock + idempotency key + audit entry.
- Cards/randomness/scores/settlement are server-authoritative; the client is untrusted.
- Run `vendor/bin/pint --dirty` after PHP edits and `php artisan test --compact` before finishing.
