# Decision Register

`agents.md` §21 says ambiguous or consequential game/financial rules must be documented and
confirmed before implementation. This file records each one, the recommended default, and its
current state. Items marked **needs confirmation** must be answered before the milestone that
depends on them is implemented.

---

## D1 — Foul settlement (blocks Milestone 4)

A player who submits an arrangement violating back ≥ middle ≥ front commits a foul. `agents.md`
defines foul detection, a clear reason, consistent scoring and audit logging, but does not
define the settlement amount.

Options:

| Option | Behaviour | Effect on zero-sum |
|---|---|---|
| A (recommended) | Fouled player loses **all three** hands to every opponent (−3 per opponent, max −9); opponents score +3 against them. | zero-sum preserved |
| B | Fouled player loses **only the hands that are out of order**; ordered hands are still compared normally. | zero-sum preserved, but the penalty depends on how many hands foul |
| C | Fouled player is disqualified from the round and the match is voided/cancelled with reservations released. | no settlement, or fee-only |

Recommended: **A**, with the foul reason stored per hand submission and an audit entry.
State: **adopted (option A)** — implemented in `RoundEvaluator` and covered by tests. A
timed-out player is treated as a foul as well (see D11).

## D2 — Hand comparison rules the engine must encode (blocks Milestone 4)

Defaults taken from Pepito, the Philippine 13-card Chinese poker variant:

- 5-card categories: Royal Flush > Straight Flush > Four of a Kind > Full House > Flush >
  Straight > Three of a Kind > Two Pair > One Pair > High Card, compared by category, then
  rank set, then kickers.
- Ace-low straight `A-2-3-4-5` is a valid 5-high straight, ranked below `2-3-4-5-6`;
  `A-K-Q-J-T` is the highest straight.
- 3-card front hand: Three of a Kind > One Pair > High Card **only**. Three-card straights and
  flushes are **not** ranked (so `2-3-4` offsuit equals `2-3-4` suited) — matches §4.
- Suits are **never** ranked, so a comparison can tie (tie = 0 game points).
- Ordering check (foul): all hands are compared on the standard category ladder, even across
  hand sizes. A front-hand trip (category 4) outranks a middle-hand two pair (category 3), so
  that arrangement is a foul; a front trip under a middle straight is legal. This matches the
  published mis-set example (three of a kind in front with only two pair in the middle).
- No royalties, no scoop bonus, no special hands — matches §4 (add later only by explicit
  instruction).

State: **adopted** (approved by the product owner; house-rule changes must be made in
`HandEvaluator`/`FoulDetector` and covered by tests).

## D3 — Cash conversion rate (blocks Milestone 7)

`1 game point = PHP X` must be configurable, never hardcoded. Default for development: a
seeded `game_point_value` setting with an explicitly test-only value, displayed as test credits.
State: **value needed** (and the currency/locale formatting convention).

## D4 — Reservation formula (blocks Milestone 5)

"Reserve enough to cover the maximum possible game loss and disclosed fees."
Recommended: `reservation = (3 × game_point_value) + platform_fee`, i.e. the maximum loss
against one opponent (−3) plus the fee. With foul settlement option A the worst case becomes
−9 against three opponents, so the reservation must be `max_loss(game_points) × conversion +
fee` derived from the settlement rules, not hardcoded.
State: **needs confirmation once D1 is decided.**

## D5 — Platform fee (blocks Milestone 7, flag stays off)

Fixed amount per participating player per completed match, configurable, disclosed before
joining, not charged for cancelled/unstarted games, never double-charged, recorded in the
ledger separately from scores. Fee feature flag ships **disabled**.
State: **amount/currency needed; enablement requires approval.**

## D6 — Roles implementation (blocks Milestone 2)

`spatie/laravel-permission` vs first-party `roles`/`permissions` tables + policies. Either is
fine; the existing `users.role` string is replaced by a derived accessor to avoid breaking
`auth.user.role` in Inertia props.
Recommended: first-party (no new dependency, full control, audit-friendly) unless the user
prefers spatie.
State: **confirm**.

## D7 — KYC and player-protection scope (blocks Milestone 3/9)

`agents.md` requires age verification, KYC, geographic eligibility, responsible-gaming limits
and self-exclusion, without naming a provider or document set. Recommended for this phase:
internal document upload + manual admin review, plus a `country`/`age` gate on the profile —
no third-party identity provider until one is selected.
State: **confirm scope/provider.**

## D8 — Test credits (blocks Milestone 7)

Development uses clearly labelled test credits that cannot be mistaken for real money
(`wallets.is_test_credit` + "TEST CREDIT" labels in the UI, not PHP amounts). Real-money
deposit/withdrawal/settlement flags stay off.
State: **confirm labelling/naming.**

## D9 — Test database for concurrency (blocks Milestone 10)

SQLite cannot exercise `lockForUpdate()`. Financial concurrency/idempotency tests need MySQL
(docker service in CI). Recommended: keep SQLite for unit/feature speed, add a MySQL suite for
wallet locking.
State: **confirm** (affects CI setup in Milestone 11).

## D10 — API auth strategy (blocks Milestone 2)

Session cookies for web, Sanctum **personal access tokens** (not SPA cookie mode) for the
future Next.js + Capacitor client, same authorization policies in both. Tokens get scoped
abilities (`read`, `play`, `wallet`).
State: **confirm.**

---

## D11 — Arrangement timeout (implemented)

A player who has not submitted when `games.arrangement_deadline_at` passes is auto-fouled
(option A from D1): the server stores their dealt cards in deal order as the submission (5/5/3),
marks the submission invalid with the reason *"Your hands were not submitted before the
deadline."*, and evaluates the round. The other three players are scored normally.

- Late submissions are rejected by `GameService::submit()` once the deadline has passed.
- `POST /games/{game}/timeout` lets a seated player ask the server to resolve the round; the
  server re-checks the deadline, so the client cannot force it early.
- `php artisan games:resolve-expired` is scheduled every minute as a backstop for rounds where
  every client disconnected.
- The table shows a server-clock-anchored countdown and polls until results arrive.

State: **adopted**. Timeout length stays configurable through
`game.arrangement_timeout_seconds` (default 120).

---

## Assumptions already adopted (documented, reversible)

- Pages stay JavaScript `.jsx` (no TypeScript migration) — matches the starter.
- The existing username-based login is kept; email login is not added.
- The TailAdmin design tokens/components are reused; a distinct dark game surface is added on
  top rather than replacing the design system.
- Money is stored as `bigint` minor units (centavos) with a `Money` value object; no decimal
  package.
- `app/Domain/*` is the new home for business logic (additive folder under `app/`).
