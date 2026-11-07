# Project: Traditional 13-Card Chinese Poker (Pusoy) Web Platform

> **Naming:** this game is called **Pepito** in the Philippines, and that is the name used
> throughout the product UI, README and docs. The rules specified below (four players, 13
> cards, 5/5/3, back ≥ middle ≥ front) are the Pepito / 13-card Chinese poker rules.

## 1. Project Overview

Build a complete, production-oriented Traditional 13-Card Chinese Poker (Pusoy) web platform using my existing GitLab repository as the foundation:

**Repository:** https://gitlab.com/czar.gaba/baseplate-react

This is an existing Laravel + React starter project. Extend and customize it into a fully functional Chinese Poker platform.

The current development scope is STRICTLY the web application.

The application must include:
- Player-facing web application.
- Administrative dashboard.
- Four-player Chinese Poker gameplay.
- Multiple game rooms with different entry levels.
- Player wallet and cash-point management.
- Game history and results.
- Real-time gameplay.
- Administrative financial management.
- Secure and auditable game settlement.

The application must be architected so a separate mobile application can be developed later using Next.js + Capacitor and connect to the same Laravel backend through its REST API.

Do not develop the mobile application in this phase.

---

# 2. Existing Technology Stack

First, inspect the existing repository and preserve its architecture.

Expected existing technologies:

- Laravel 13
- PHP 8.3+
- React 19
- Inertia.js 3
- Tailwind CSS 4
- Vite 7
- MySQL 8
- Pest
- Laravel Pint

Additional technologies to integrate when appropriate:

- Laravel Sanctum for API authentication.
- Laravel Reverb for real-time communication.
- Redis for broadcasting, caching, queues, and coordination.
- Docker Compose for deployment.
- GitLab CI/CD for automated testing and deployment.

### Important instructions

Do not replace the existing frontend framework.

Do not:
- Replace React with Vue.
- Replace Inertia with another frontend framework.
- Create a separate React frontend repository.
- Create an Ionic Vue application.
- Create a Next.js application in this phase.
- Rebuild existing authentication or layouts unnecessarily.
- Replace existing dependencies without a technical reason.
- Remove working features from the starter project.

Reuse existing:
- Authentication.
- Dashboard structure.
- Layouts.
- Components.
- Tailwind configuration.
- Vite configuration.
- Existing coding conventions.

Inspect the repository before making changes.

If the actual repository differs from the expected stack, document the differences and follow the actual implementation.

---

# 3. Architecture

Use a Laravel monolithic application with React and Inertia.

The same Laravel application must handle:

1. Player-facing web pages.
2. Admin dashboard.
3. Application business logic.
4. Chinese Poker game engine.
5. Wallet and financial transactions.
6. Room and matchmaking management.
7. Versioned REST API.
8. Real-time broadcasting.

Suggested architecture:

- Laravel: backend and business logic.
- React + Inertia: web interface.
- MySQL: persistent application data.
- Redis: real-time broadcasting, queues, caching, and locks.
- Laravel Reverb: WebSocket communication.
- Laravel Sanctum: API authentication.
- Docker Compose: application infrastructure.

### Backend separation

Keep the business logic independent of the React/Inertia interface.

Organize the backend into logical services or domains:

- Authentication
- Players
- Wallet
- Financial transactions
- Game engine
- Card evaluation
- Scoring
- Rooms
- Matchmaking
- Real-time events
- Administration
- Reporting
- Audit logging

Controllers must remain lightweight.

Business rules must live in dedicated services, actions, or domain classes.

The web interface must not be responsible for calculating authoritative game scores or financial balances.

### API preparation

Create a versioned API under:

`/api/v1`

The API must support future mobile clients without requiring changes to the game engine or wallet implementation.

Include:
- Authentication.
- Player profile.
- Room listings.
- Match participation.
- Game state.
- Hand submission.
- Game results.
- Wallet balances.
- Transaction history.
- Deposit requests.
- Withdrawal requests.

The API must enforce the same authorization and validation rules as the web application.

Do not build a separate mobile application now.

---

# 4. Game Rules — Traditional 13-Card Chinese Poker

Implement Traditional 13-card Chinese Poker, also known as Pusoy.

### Basic rules

- Exactly 4 players per table.
- Standard 52-card deck.
- No jokers.
- Every player receives 13 unique cards.
- Players arrange their cards into three hands.

Hand arrangement:

| Position | Cards |
|---|---:|
| Back hand | 5 |
| Middle hand | 5 |
| Front hand | 3 |
| Total | 13 |

The player must arrange the hands in the following order:

**Back hand > Middle hand > Front hand**

The back hand must be stronger than or equal to the middle hand, and the middle hand must be stronger than or equal to the front hand according to the applicable Chinese Poker hand comparison rules. If the arrangement violates the required ordering, the player commits a foul.

Implement the exact Traditional Chinese Poker ordering and tie-breaking rules in the game engine.

### Five-card hand rankings

Implement standard five-card poker rankings:

1. Royal Flush.
2. Straight Flush.
3. Four of a Kind.
4. Full House.
5. Flush.
6. Straight.
7. Three of a Kind.
8. Two Pair.
9. One Pair.
10. High Card.

Handle all card-rank and kicker comparisons correctly.

### Three-card front hand rankings

The front hand has only three cards.

Implement:
1. Three of a Kind.
2. One Pair.
3. High Card.

Use correct tie-breaking rules for three-card hands.

Do not apply five-card rankings to the front hand.

### Game evaluation

Every player is compared against every other player.

For four players:

- 6 unique player pairings.
- 3 hand comparisons per pairing.
- 18 individual hand comparisons in total.

Compare:
- Back against back.
- Middle against middle.
- Front against front.

All comparisons must be calculated on the server.

### Scoring rules

Use the following initial scoring system:

- Win a hand: +1 game point.
- Lose a hand: -1 game point.
- Tie a hand: 0 game points.
- No scoop bonus.
- No royalty bonus.
- No special-hand bonus initially.

Against one opponent:

- Maximum score: +3.
- Minimum score: -3.

The total aggregate game score across four players must equal zero before platform fees.

Example:

| Player | Game Points |
|---|---:|
| Player A | +2 |
| Player B | -1 |
| Player C | -2 |
| Player D | +1 |
| Total | 0 |

This is an illustrative scoring example.

### Foul handling

A foul occurs when a player submits an invalid hand arrangement.

Implement:
- Server-side foul detection.
- Clear foul reason.
- Consistent foul scoring.
- Audit logging.
- Prevention of client-side score manipulation.

Define the exact foul settlement behavior before implementation and document it.

Do not silently invent additional bonus or penalty rules.

### Game engine requirements

The game engine must:

- Generate a standard 52-card deck.
- Shuffle securely using server-side cryptographically secure randomness.
- Deal 13 cards to each player.
- Ensure no duplicate cards.
- Ensure all dealt cards belong to the original deck.
- Validate hand arrangements.
- Evaluate five-card hands.
- Evaluate three-card hands.
- Detect fouls.
- Compare all players.
- Calculate individual hand scores.
- Calculate aggregate scores.
- Produce a complete game result.
- Record game history.

Use deterministic tests for hand evaluation and scoring.

The frontend must never be trusted to determine game results.

---

# 5. Game Rooms

Support multiple rooms with configurable entry levels.

Each room should have:

- Room name.
- Room description.
- Entry requirement.
- Entry cash-point amount.
- Maximum players: 4.
- Minimum players: 4.
- Game status.
- Platform fee configuration.
- Room availability.
- Optional room restrictions.

Players must be able to:

- Browse available rooms.
- View entry requirements.
- View the disclosed platform fee.
- Join an available room.
- Leave before the game starts.
- View other seated players.
- Ready up.
- Wait for all four players.
- Start the game when the requirements are satisfied.

### Room lifecycle

Suggested states:

- Waiting.
- Filling.
- Ready.
- In Progress.
- Completed.
- Cancelled.

Implement proper handling for:

- Player disconnects.
- Player leaving before game start.
- Inactive players.
- Matchmaking timeouts.
- Incomplete tables.
- Cancelled matches.
- Reconnection during a game.

A game must not begin until all four players are authorized and the necessary funds have been reserved.

---

# 6. Player Web Application

Create a modern, responsive player-facing interface.

The interface should be clean, professional, and suitable for desktop, tablet, and mobile browsers.

Use the existing React and Tailwind design system.

### Player authentication

Implement:

- Registration.
- Login.
- Logout.
- Forgot password.
- Password reset.
- Email verification.
- Account status.
- Session management.

### Player dashboard

Display:

- Player account information.
- Available cash points.
- Reserved cash points.
- Recent transactions.
- Recent games.
- Total games played.
- Wins and losses.
- Overall game-point statistics.
- Available rooms.
- Announcements.
- Account verification status.

### Lobby

The lobby should display available game rooms.

Each room card should show:

- Room name.
- Entry amount.
- Platform fee.
- Total required reservation.
- Number of seated players.
- Room status.
- Join button.

Clearly display all fees before a player joins.

### Poker table

Create an interactive four-player table.

Display:

- Four player positions.
- Player avatars.
- Player names.
- Player status.
- Card information.
- Game status.
- Countdown timers where applicable.
- Connection status.
- Game notifications.

The current player's cards must be private.

Never expose another player's private cards through frontend state, API responses, WebSocket events, or browser inspection before the appropriate reveal phase.

### Card arrangement interface

Implement an intuitive drag-and-drop interface.

Players must be able to:

- View all 13 cards.
- Move cards between hand positions.
- Arrange five cards in the back hand.
- Arrange five cards in the middle hand.
- Arrange three cards in the front hand.
- Rearrange cards.
- View the number of cards assigned to each position.
- Validate their arrangement before submission.
- Submit their completed arrangement.

The interface must clearly communicate:

- Back: 5 cards.
- Middle: 5 cards.
- Front: 3 cards.

Display validation errors before submission where possible.

However, the server must independently validate every submitted arrangement.

Support desktop mouse interaction and browser touch interaction.

### Game results

After the game finishes, display:

- All four players.
- Each player's submitted hands.
- Back-hand comparison.
- Middle-hand comparison.
- Front-hand comparison.
- Individual hand results.
- Game-point totals.
- PHP-equivalent cash-point results.
- Platform fees.
- Final wallet settlement.
- Complete game summary.

Players must be able to access previous game results.

### Wallet

Display:

- Available balance.
- Reserved balance.
- Deposit history.
- Withdrawal history.
- Winnings.
- Losses.
- Platform fees.
- Transaction history.

Separate game points from cash points.

Do not confuse game scores with wallet balances.

### Profile

Include:

- Personal information.
- Profile picture.
- Account information.
- Password changes.
- Verification status.
- Responsible gaming settings.
- Account security.

---

# 7. Wallet and Cash-Point System

Implement a secure financial ledger.

The wallet must be independent of the game-point scoring system.

### Game points and cash points

Game points represent the result of a match.

Cash points represent the player's financial balance.

The conversion rate must be configurable.

Example only:

If:

`1 game point = PHP 10`

Then:

| Game Points | Cash-Point Equivalent |
|---:|---:|
| +2 | +PHP 20 |
| -1 | -PHP 10 |
| -2 | -PHP 20 |
| +1 | +PHP 10 |

The PHP conversion rate must be configurable through the admin settings.

Never hardcode the conversion rate.

Use integer arithmetic or exact decimal arithmetic for monetary calculations.

Never use floating-point calculations for financial balances.

### Wallet balances

Track:

- Available balance.
- Reserved balance.
- Deposited amount.
- Withdrawn amount.
- Winnings.
- Losses.
- Platform fees.

Maintain a complete transaction history.

### Reservation before playing

Before joining a game, the server must verify that the player has sufficient funds.

Reserve enough funds to cover:

- Maximum possible game loss.
- Disclosed platform fees.
- Any other explicitly authorized charges.

Prevent players from joining if they cannot cover the required reservation.

Use database transactions and row-level locking to prevent simultaneous spending.

### Double-entry ledger

Implement an immutable double-entry financial ledger.

Every financial movement must have corresponding debit and credit entries.

Examples:

- Deposit.
- Withdrawal.
- Game reservation.
- Reservation release.
- Game settlement.
- Winnings.
- Losses.
- Platform fee.
- Administrative adjustment.

Requirements:

- No deleting completed financial transactions.
- No silently modifying completed ledger entries.
- Corrections must use compensating transactions.
- Every movement must have a unique reference.
- Every transaction must be auditable.
- Settlement must be atomic.
- Settlement must be idempotent.
- Prevent duplicate charges and duplicate payouts.

Use database transactions, row locking, and idempotency keys.

---

# 8. Deposits

Create a deposit request workflow.

Deposit statuses:

- Pending.
- Under Review.
- Approved.
- Rejected.
- Cancelled.

The deposit interface should support:

- Amount.
- Payment method.
- Reference number.
- Payment instructions.
- Optional proof-of-payment upload.
- Deposit history.
- Status tracking.

Do not automatically credit a player's wallet simply because an image was uploaded or a payment redirect was completed.

Payment must be independently verified.

Create an administrative deposit review interface.

All approved deposits must create corresponding ledger entries.

---

# 9. Withdrawals

Create a secure withdrawal workflow.

Withdrawal statuses:

- Pending.
- Under Review.
- Approved.
- Processing.
- Completed.
- Rejected.
- Cancelled.

The player must be able to:

- Submit a withdrawal request.
- Select an approved payout method.
- Enter the amount.
- View withdrawal status.
- Review withdrawal history.

Reserve the requested amount immediately when the request is accepted.

Release the reservation if a request is rejected or cancelled, where applicable.

Only mark a withdrawal completed after independent confirmation of the actual payout.

Protect payout details and restrict access to authorized administrators.

All withdrawal operations must be recorded in the ledger.

---

# 10. Platform Fee and Revenue Model

The platform earns revenue through a fixed platform fee per participating player per completed match.

The platform fee must be configurable.

Settings should include:

- Fee amount.
- Fee currency.
- Fee activation status.
- Applicable rooms.
- Effective date.
- Administrative audit history.

Important rules:

- The fee must be disclosed before joining.
- The fee must be reserved or otherwise accounted for before the game starts.
- Do not silently deduct fees.
- Do not charge for cancelled or unstarted games.
- Do not charge a fee twice.
- Record fees independently from game scores.
- Record fee revenue in the financial ledger.
- Keep the fee disabled by default until all required approvals are obtained.

Do not invent percentage-based fees or additional charges.

---

# 11. Real-Time Gameplay

Use Laravel Reverb and Redis for real-time communication.

Implement private, authenticated WebSocket channels.

Real-time events should cover:

- Player joined room.
- Player left room.
- Player ready.
- Table filled.
- Game started.
- Cards dealt.
- Player submitted hands.
- All hands submitted.
- Game evaluated.
- Game completed.
- Wallet settlement completed.
- Player disconnected.
- Player reconnected.
- Timer updates.

### Private card delivery

Each player must receive only their own private cards.

Never broadcast all players' cards to a shared room channel.

Use authenticated private channels and server-side authorization.

### Reconnection

Players should be able to reconnect during an active game.

After reconnecting, the server must return only the state that the player is authorized to see.

Do not allow reconnecting players to change previously submitted cards.

Implement timeout and recovery policies.

### Server authority

The server controls:

- Deck generation.
- Shuffle.
- Card dealing.
- Game timing.
- Hand submission.
- Validation.
- Scoring.
- Settlement.

Never trust frontend-generated game states or scores.

---

# 12. Administrative Dashboard

Create a separate administrative dashboard using the existing React/Inertia architecture.

The admin dashboard must have its own navigation and layout while reusing existing design components.

### Admin overview

Display:

- Total registered players.
- Verified players.
- Active players.
- Games played.
- Active games.
- Completed games.
- Total deposits.
- Total withdrawals.
- Total platform fees.
- Total wallet balances.
- Pending financial requests.

### Player management

Administrators should be able to:

- View players.
- Search players.
- View player profiles.
- View account status.
- View verification status.
- Suspend accounts.
- Reactivate accounts.
- Review game history.
- Review wallet transactions.

Administrative actions must be logged.

### KYC management

Provide interfaces for:

- Viewing verification submissions.
- Reviewing documents.
- Approving verification.
- Rejecting verification.
- Recording rejection reasons.
- Viewing verification status.

Restrict document access to authorized administrators.

### Room management

Administrators should be able to:

- Create rooms.
- Edit rooms.
- Enable or disable rooms.
- Configure entry requirements.
- Configure platform fees.
- Monitor active tables.
- View room statistics.

### Game monitoring

Administrators should be able to:

- View active games.
- View completed games.
- Inspect game history.
- Review submitted hands.
- Review game results.
- Review foul decisions.
- Inspect game audit records.

Do not allow administrators to silently alter completed game results.

Any authorized correction must create an auditable record.

### Deposit management

Provide:

- Deposit request list.
- Deposit details.
- Proof-of-payment review.
- Approval.
- Rejection.
- Rejection reasons.
- Deposit history.

### Withdrawal management

Provide:

- Withdrawal request list.
- Player details.
- Payout details.
- Approval.
- Processing.
- Completion confirmation.
- Rejection.
- Withdrawal history.

Use separate permissions for financial approval and payout completion where practical.

### Ledger management

Administrators with appropriate permissions can:

- View all ledger transactions.
- Filter by player.
- Filter by transaction type.
- Filter by date.
- View debit and credit entries.
- View transaction references.
- View settlement details.
- Export reports.

Do not provide direct editing or deletion of completed ledger entries.

### Reports

Include:

- Player activity.
- Game statistics.
- Room performance.
- Deposit reports.
- Withdrawal reports.
- Platform revenue.
- Fee reports.
- Wallet reconciliation.
- Financial summaries.

### Settings

Provide configurable settings for:

- Game-point conversion rate.
- Room entry levels.
- Fixed platform fee.
- Deposit methods.
- Withdrawal methods.
- Minimum and maximum transaction amounts.
- Responsible gaming limits.
- Feature flags.
- Maintenance mode.

All financial setting changes must be logged with the administrator, previous value, new value, and timestamp.

---

# 13. Roles and Permissions

Implement role-based access control.

Suggested roles:

### Super Administrator

Full system access.

Can manage:
- Administrators.
- Roles and permissions.
- Global settings.
- Financial configuration.
- Rooms.
- Players.
- Games.
- Reports.
- Audit logs.

### Finance Administrator

Can manage:
- Deposits.
- Withdrawals.
- Financial reviews.
- Ledger viewing.
- Financial reports.

Must not automatically receive permission to modify game-engine settings or manage other administrators.

### Game Administrator

Can manage:
- Rooms.
- Active game monitoring.
- Game history.
- Player game issues.

Cannot directly modify wallet balances.

### Support Administrator

Can:
- View player accounts.
- Review account issues.
- View game history.
- Review support requests.

Cannot approve financial transactions unless explicitly granted permission.

### Player

Can:
- Access their own account.
- Join authorized rooms.
- Play games.
- View their own game history.
- View their own wallet.
- Submit deposit and withdrawal requests.
- Manage their own profile.

Enforce authorization on the backend, not just by hiding frontend navigation.

---

# 14. Security and Compliance

This platform includes real-money financial functionality and must be treated as a regulated gaming product.

Do not enable real-money wagering, deposits, withdrawals, or cash-equivalent game settlement in production until all applicable legal authorizations, regulatory requirements, and payment-provider approvals have been obtained.

For operation in the Philippines, obtain appropriate PAGCOR and legal guidance before launching any real-money gaming functionality.

Implement the following safeguards:

### Player protection

- Age verification.
- KYC verification.
- Geographic eligibility checks.
- Account security.
- Responsible gaming settings.
- Deposit limits.
- Withdrawal limits.
- Session limits.
- Self-exclusion.
- Account suspension.
- Transaction monitoring.

### Application security

- CSRF protection.
- XSS protection.
- SQL injection prevention.
- Rate limiting.
- Secure password hashing.
- Session security.
- Authorization policies.
- Private WebSocket authorization.
- Secure file uploads.
- Input validation.
- Audit logging.
- Sensitive data encryption.
- Secrets management.

### Financial security

- Double-entry ledger.
- Idempotency.
- Atomic settlement.
- Database transactions.
- Row-level locking.
- Reconciliation.
- Immutable completed transactions.
- Administrative audit logs.
- Fraud monitoring.

### Feature flags

Real-money features must be disabled by default.

Create server-side feature flags for:

- Real-money deposits.
- Real-money withdrawals.
- Cash-equivalent game settlement.
- Platform fees.
- Real-money rooms.

Disabling a feature must be enforced by backend services and APIs, not only the frontend.

Do not provide mechanisms to bypass regulatory or payment-provider restrictions.

For development, use clearly identified test credits that cannot be mistaken for real money.

---

# 15. Database Design

Design a normalized and maintainable database.

Potential entities include:

### Users and access

- users
- player_profiles
- roles
- permissions
- model_has_roles
- model_has_permissions
- account_verifications

### Rooms

- rooms
- room_settings
- room_players
- room_sessions

### Games

- games
- game_players
- game_cards
- game_hands
- game_hand_submissions
- game_hand_results
- game_player_results
- game_audit_logs

### Wallet

- wallets
- wallet_accounts
- wallet_transactions
- wallet_ledger_entries
- wallet_reservations
- deposits
- withdrawals
- payment_methods
- payout_methods

### Platform settings

- system_settings
- game_settings
- room_settings
- platform_fee_settings
- feature_flags

### Auditing

- audit_logs
- administrative_actions
- security_events

These are suggested entities, not a requirement to create every table exactly as listed.

Inspect existing migrations and models first.

Avoid duplicating existing functionality.

Use foreign keys, indexes, unique constraints, appropriate decimal or integer types, and database transactions.

Document important relationships.

---

# 16. Frontend Design Requirements

Create a polished, modern gaming platform interface.

Use the existing starter project's design system.

The UI should feel like a professional online card-game platform.

### Design direction

- Clean and modern.
- Dark gaming interface where appropriate.
- Clear typography.
- Consistent spacing.
- Responsive layout.
- Professional card design.
- Clear game status indicators.
- Subtle animations.
- Accessible color contrast.
- Loading states.
- Empty states.
- Error states.
- Confirmation dialogs.

### Player interface

Prioritize:
- Easy room discovery.
- Clear wallet information.
- Simple card arrangement.
- Readable game results.
- Clear platform fee disclosure.

### Admin interface

Prioritize:
- Data visibility.
- Tables and filters.
- Financial summaries.
- Transaction review.
- Clear permission boundaries.
- Auditability.

Do not overload the interface with unnecessary animations.

Do not sacrifice usability for visual effects.

Use reusable React components.

Keep components maintainable and follow the existing repository conventions.

---

# 17. Docker and Deployment

The target production environment is:

- Ubuntu 24.04 LTS VPS.
- Docker installed.
- aaPanel installed.
- Nginx managed by aaPanel.
- Cloudflare managing DNS and proxy.
- GitLab repository.

Use Docker Compose to manage the application services.

### Suggested containers/services

1. Laravel application / PHP-FPM.
2. MySQL 8.
3. Redis.
4. Laravel queue worker.
5. Laravel scheduler.
6. Laravel Reverb.

The application image may be reused for different Laravel processes such as PHP-FPM, queue workers, scheduler, and Reverb.

React assets should be compiled using Vite and served as part of the Laravel application.

Do not create a separate React production container.

### Nginx

Prefer using aaPanel's existing host Nginx as the public reverse proxy.

Configure it to forward requests to the Laravel application container.

Do not expose MySQL or Redis publicly.

Only expose the necessary application and real-time ports.

### Cloudflare and HTTPS

Configure:

- Domain DNS.
- Cloudflare proxy where appropriate.
- SSL/TLS.
- HTTPS redirect.
- Secure headers.
- WebSocket proxy support.
- Appropriate firewall rules.

Use environment variables for credentials and secrets.

Never commit production `.env` files.

### Docker requirements

Create:

- Dockerfile.
- docker-compose.yml.
- Production environment example.
- Health checks.
- Persistent MySQL storage.
- Persistent required application storage.
- Queue worker configuration.
- Scheduler configuration.
- Reverb configuration.
- Logging configuration.

Include backup and restoration procedures.

Do not expose database credentials or internal services.

---

# 18. GitLab CI/CD

Use the existing GitLab repository.

Create a GitLab CI/CD pipeline that supports:

1. Dependency installation.
2. PHP syntax checks.
3. Laravel Pint.
4. Pest tests.
5. Frontend build.
6. Production image build.
7. Deployment to the VPS.

Deployment requirements:

- Use GitLab CI/CD variables for secrets.
- Use SSH keys for deployment.
- Avoid hardcoding credentials.
- Pull the latest approved code.
- Build or pull the correct Docker image.
- Run database migrations safely.
- Restart relevant containers.
- Verify application health.
- Keep a rollback procedure.

Do not automatically run destructive database commands in production.

Do not run seeders that overwrite production data.

---

# 19. Testing Requirements

Write meaningful automated tests.

### Game engine tests

Test:
- Deck contains exactly 52 cards.
- No duplicate cards.
- Each player receives 13 cards.
- Five-card hand evaluation.
- Three-card hand evaluation.
- Tie-breaking.
- Foul detection.
- Hand ordering.
- Pairwise comparison.
- Four-player score aggregation.
- Zero-sum scoring before fees.
- PHP conversion calculations.

### Wallet tests

Test:
- Deposit approval.
- Deposit rejection.
- Withdrawal requests.
- Withdrawal reservation.
- Reservation release.
- Game entry reservation.
- Game settlement.
- Platform fee calculation.
- Duplicate transaction prevention.
- Idempotency.
- Concurrent wallet operations.
- Ledger balancing.
- Reconciliation.

### Authorization tests

Test:
- Player access restrictions.
- Admin access restrictions.
- Finance permissions.
- Private card access.
- API authorization.
- WebSocket channel authorization.

### Integration tests

Test:
- Player registration.
- Room creation.
- Four-player matchmaking.
- Game start.
- Card distribution.
- Hand submission.
- Game evaluation.
- Result broadcasting.
- Wallet settlement.

Use Pest and follow existing project testing conventions.

---

# 20. Development Milestones

Implement the project in stages.

Do not attempt to build everything in one large change.

## Milestone 1 — Repository Inspection and Foundation

- Inspect the complete repository.
- Understand existing authentication.
- Understand existing layouts and components.
- Review existing migrations.
- Review frontend conventions.
- Identify reusable functionality.
- Document the architecture.
- Identify missing dependencies.
- Create an implementation plan.

Do not replace the starter template immediately.

## Milestone 2 — Database and Backend Foundation

- Create required database models.
- Create migrations.
- Implement relationships.
- Implement roles and permissions.
- Configure Sanctum.
- Configure API versioning.
- Implement audit logging.
- Prepare backend service structure.

## Milestone 3 — Player Application

- Registration and login.
- Player dashboard.
- Profile.
- Wallet interface.
- Transaction history.
- Room lobby.

## Milestone 4 — Game Engine

- Deck generation.
- Secure shuffle.
- Card distribution.
- Poker hand evaluation.
- Front-hand evaluation.
- Hand validation.
- Foul detection.
- Pairwise scoring.
- Four-player score aggregation.
- Game history.

Complete automated game-engine tests before integrating real-time gameplay.

## Milestone 5 — Room and Matchmaking

- Room management.
- Multiple entry levels.
- Four-player seating.
- Ready system.
- Match authorization.
- Entry reservation.
- Game lifecycle.

## Milestone 6 — Interactive Poker Table

- Four-player table UI.
- Private card delivery.
- Card arrangement.
- Drag-and-drop.
- Hand submission.
- Validation.
- Timers.
- Game results.

## Milestone 7 — Wallet and Financial System

- Double-entry ledger.
- Cash-point conversion.
- Reservations.
- Deposits.
- Withdrawals.
- Platform fees.
- Settlement.
- Reconciliation.

Use test credits during development.

## Milestone 8 — Real-Time Communication

- Laravel Reverb.
- Redis.
- Private channels.
- Game events.
- Player presence.
- Reconnection.
- Timeout handling.

## Milestone 9 — Administrative Dashboard

- Admin overview.
- Player management.
- KYC management.
- Room management.
- Game monitoring.
- Deposit management.
- Withdrawal management.
- Ledger.
- Reports.
- Settings.
- Audit logs.

## Milestone 10 — Security and Testing

- Authorization.
- Financial concurrency tests.
- Game integrity tests.
- API tests.
- WebSocket tests.
- Rate limiting.
- Security review.
- Backup and recovery.

## Milestone 11 — Docker and Production Deployment

- Production Docker configuration.
- MySQL and Redis.
- Nginx reverse proxy.
- Cloudflare.
- HTTPS.
- GitLab CI/CD.
- Monitoring.
- Logging.
- Production readiness checklist.

Real-money functionality must remain disabled until all applicable approvals are confirmed.

---

# 21. Coding Agent Working Rules

Follow these instructions throughout development.

1. Inspect before modifying.
2. Do not replace working starter functionality without a reason.
3. Follow the existing code conventions.
4. Use reusable components.
5. Keep controllers lightweight.
6. Keep game logic in dedicated backend services.
7. Keep wallet logic separate from game scoring.
8. Never trust client-side card evaluation or scores.
9. Never expose private cards to unauthorized players.
10. Never use floating-point arithmetic for financial balances.
11. Use database transactions for financial operations.
12. Use row locking for wallet reservations and settlement.
13. Make financial operations idempotent.
14. Add automated tests for critical business logic.
15. Do not introduce unnecessary dependencies.
16. Do not create a mobile application.
17. Do not create an Ionic Vue application.
18. Do not create a Next.js application in this phase.
19. Keep the REST API reusable for a future Next.js + Capacitor application.
20. Do not remove existing working functionality.
21. Document significant architecture decisions.
22. Update documentation when changing database structure or API behavior.
23. Never hardcode financial settings.
24. Do not enable real-money functionality without required approvals.
25. Complete each milestone with working tests before proceeding.

When a task is too large, break it into smaller implementation tasks.

Do not claim a feature is complete unless it has been implemented and tested.

If a requirement is unclear, document the assumption and ask for clarification before implementing a potentially consequential game or financial rule.

---

# 22. First Task — Start Here

Do not immediately implement the entire application.

Your first task is to inspect the existing GitLab repository.

Provide:

1. Current repository structure.
2. Existing Laravel version and dependencies.
3. Existing React and Inertia structure.
4. Existing authentication implementation.
5. Existing dashboard and layouts.
6. Existing reusable UI components.
7. Existing database migrations and models.
8. Existing testing setup.
9. Existing Docker configuration, if any.
10. Missing packages and integrations.
11. Potential conflicts with the proposed architecture.
12. Recommended implementation structure.
13. A milestone-based implementation plan.

Then prepare the foundation changes required for Milestone 1.

Preserve the existing starter project and make incremental, reviewable changes.

**The goal is to turn the existing baseplate-react repository into a complete, secure, scalable Traditional 13-card Chinese Poker web platform without unnecessarily rebuilding its existing foundation.**