# Phase 15D - Login Protection and Account Recovery Instructions

## Status

Complete locally on `feature/phase-15d-login-protection`, based on Phase 15C commit
`d833455`.

No production deployment, database migration, account unlock, password reset, or
credential rotation was performed.

## Authentication behavior

Login email addresses are trimmed and lowercased before validation, lookup, and
throttling. Database lookup is case-insensitive.

Every failed login is constrained by two independent cache-backed limits:

- normalized email: 5 attempts per 300 seconds; and
- client IP: 20 attempts per 300 seconds.

Cache keys contain SHA-256 digests rather than raw email addresses or IP addresses.
A successful login clears only its email limit. It does not clear the shared IP limit,
so a valid account cannot be used to bypass IP-wide protection.

Missing accounts receive a dummy password-hash check to reduce timing differences.
Nonexistent accounts, incorrect passwords, inactive accounts, temporarily locked
accounts, and rate-limited attempts all receive the same message:

```text
Sign-in failed. Check your credentials or try again later.
```

The response does not state whether an account exists, is locked, or is inactive.

## Temporary account lockout

Existing active accounts record failed-password state in the Phase 15B fields. The
default progression is:

- attempts 5 through 9: 5-minute temporary lock;
- attempts 10 through 14: 15-minute temporary lock; and
- attempt 15 and later: 30-minute temporary lock.

Thirty minutes is the fixed maximum. No unauthenticated failure can create a permanent
lock. An expired lock is cleared automatically on the next login attempt and records an
automatic unlock audit event. A successful login clears the accumulated failure state.

The authorized DILG administrator can also clear a barangay account's lock and failure
counter from Account Management. Manual unlock clears the affected email throttle but
does not clear the IP-wide throttle. Barangay staff cannot call the unlock route.

## Audit and recovery safety

- A new temporary lock records `account_locked` with the affected account, timestamp,
  IP address, and bounded user agent.
- Automatic expiry records `account_unlocked` with no actor and the affected account.
- Manual DILG unlock records the administrator as actor and the barangay account as
  target.
- No password, submitted credential, throttle key, or arbitrary request payload is
  accepted by the audit logger.
- The Forgot Password action provides contact instructions for the authorized DILG
  administrator. It does not pretend to send a reset link.

## Configuration

The non-secret operational settings are documented in `.env.example`:

- `LOGIN_EMAIL_MAX_ATTEMPTS=5`
- `LOGIN_IP_MAX_ATTEMPTS=20`
- `LOGIN_RATE_LIMIT_DECAY_SECONDS=300`
- `LOGIN_LOCKOUT_THRESHOLD=5`

Lock durations remain controlled in reviewed application configuration as
`[5, 15, 30]` minutes so an environment variable cannot accidentally create an
unbounded or permanent lock.

## Verification

- Phase 15D focused suite: 8 passed, 64 assertions.
- All Phase 15 security tests: 26 passed, 236 assertions.
- Complete Laravel regression suite: 235 passed, 2,352 assertions.
- Production Vite build: passed.
- Laravel Pint on changed PHP files: passed.
- Composer configuration validation: passed.
- Composer audit: no known security advisories.

The tests cover normalized email login, independent email and IP limits, temporary
locking, generic errors, automatic expiry, escalating locks with a fixed maximum,
successful post-expiry login, DILG-only manual unlock, audit records, and recovery text.

## Files changed

- `.env.example`
- `app/Http/Controllers/AccountManagementController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Services/LoginProtectionService.php`
- `config/security.php`
- `resources/views/account-management/index.blade.php`
- `routes/web.php`
- `tests/Feature/Phase15DLoginProtectionTest.php`
- `CIVICLEAR_SECURITY_IDENTITY_AI_ROADMAP.md`
- `docs/phase-records/PHASE_15D_LOGIN_PROTECTION.md`

## Deployment and rollback boundary

Phase 15D adds no new migration beyond Phase 15B. It is not authorization to deploy or
rotate credentials. The full Phase 15 flow must first be exercised on an immutable
staging revision with synthetic accounts, including cache behavior that matches the
target Azure environment.

If a login-protection defect is found before credential cutover, route traffic back to
the prior immutable revision. The database lock fields use backward-compatible
defaults, so the previous application revision can continue operating. Preserve audit
records and use a reviewed forward fix; do not wipe, reseed, or roll back production
security history casually.

## Next phase

Phase 16A introduces an optional reporter name with an explicit privacy notice. Blank
names must remain anonymous, existing reports must remain unchanged, and names must be
excluded from public tracking, GIS, analytics, exports, logs, and URLs.
