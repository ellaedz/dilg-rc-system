# Phase 15C - DILG Account Management and Forced Password Change

## Status

Complete locally on `feature/phase-15c-account-management`, based on Phase 15B commit
`80307fc`.

No production migration was run, no branch was pushed, and no live account password or
session was changed. Existing reports, photos, GIS records, AI results, timelines, and
barangay assignments were not modified.

## Implemented flow

### DILG account management

The new DILG-only Account Management page lists the 26 barangay staff accounts with:

- assigned barangay;
- account name and email;
- active or temporarily locked state;
- current or password-change-required state; and
- last recorded password-change time.

The page never retrieves or renders an existing password or password hash. Each reset
form accepts an administrator-entered temporary password and confirmation, uses the
central Phase 15B policy, hashes the value before persistence, marks the account as
requiring a password change, increments its session version, deletes its database
sessions, and writes a password-reset audit event.

The route and request authorization both require the DILG administrator role, and the
target must be a barangay staff account. A barangay user cannot list accounts, reset
another user, or target the DILG account.

### Required first-login change

After successful authentication, an account with `must_change_password = true` is sent
directly to the secure Change Password page. Middleware prevents that account from
opening dashboards, reports, GIS, analytics, exports, or authenticated staff APIs.
Only the password-change page and logout remain available.

The forced-change layout intentionally omits operational navigation so users cannot be
misled into thinking their workspace is available before the security step is complete.

### Self-service password change

Every authenticated DILG or barangay account can open Change Password from the account
menu or profile page. The form requires:

- the current password;
- a new password that satisfies the centralized 16-to-128-character policy; and
- matching confirmation.

Successful changes retain the previous one-way hash in bounded password history,
store the new value only as a hash, clear the forced-change flag, increment the session
version, invalidate all other database sessions, regenerate the current session ID,
and write a password-change audit event. The current session receives the new version
and remains authenticated.

### Session invalidation

Two complementary controls are used:

1. database-backed sessions belonging to the affected user are deleted; and
2. every authenticated session carries the account's `session_version`.

The second control invalidates stale sessions even if the session storage driver is
changed. For backward compatibility, an existing active session with no version is
accepted and initialized only while the account is still at version `1`. After any
reset or password change, a missing or stale version is rejected.

## Password and audit data handling

- Reset and change forms use password inputs and never repopulate submitted values.
- Passwords are not placed in query strings, routes, flash messages, logs, audit
  payloads, HTML responses, or API responses.
- Security audit events accept only a typed action, actor, affected account, timestamp,
  IP address, and bounded user agent.
- Tests generate random values in memory instead of committing password examples.
- The login recovery text now directs staff to the authorized DILG administrator and
  explicitly states that CIVICLEAR cannot display existing passwords.

## Verification

- Phase 15C focused suite: 10 passed, 143 assertions.
- Complete Laravel regression suite: 227 passed, 2,288 assertions.
- Production Vite build: passed.
- Laravel Pint on changed PHP files: passed.
- Composer configuration validation: passed.
- Composer audit: no known security advisories.

The regression suite includes existing report/GIS authorization, PDF and CSV exports,
private photo access, mobile reporting contracts, and AI workflow tests.

## Files changed

- `app/Http/Controllers/AccountManagementController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/PasswordController.php`
- `app/Http/Middleware/EnsureSecuritySession.php`
- `app/Http/Middleware/RequireCompletedPasswordChange.php`
- `app/Http/Requests/AdminResetPasswordRequest.php`
- `app/Http/Requests/ChangeOwnPasswordRequest.php`
- `app/Models/User.php`
- `app/Services/PasswordManager.php`
- `app/Services/SecuritySessionManager.php`
- `bootstrap/app.php`
- `resources/views/account-management/index.blade.php`
- `resources/views/auth/change-password.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/barangay-app.blade.php`
- `resources/views/layouts/dilg-app.blade.php`
- `resources/views/layouts/security.blade.php`
- `resources/views/profile/index.blade.php`
- `routes/api.php`
- `routes/web.php`
- `tests/Feature/Phase15CAccountManagementTest.php`
- `CIVICLEAR_SECURITY_IDENTITY_AI_ROADMAP.md`
- `docs/phase-records/PHASE_15C_ACCOUNT_MANAGEMENT.md`

Laravel Pint also normalized the two existing role middleware files without changing
their behavior:

- `app/Http/Middleware/BarangayStaffMiddleware.php`
- `app/Http/Middleware/DilgAdminMiddleware.php`

## Deployment and rollback boundary

This phase is not authorization to deploy or rotate credentials. It depends on the
Phase 15B migration and must first be exercised in an isolated staging revision with
synthetic accounts.

Before any production release, take and verify a new backup, record baseline counts,
deploy an immutable revision, run the additive migration once, and test DILG and
barangay flows without changing a live password. If a pre-cutover defect occurs, route
traffic back to the previous immutable revision. Do not run `migrate:fresh`, `db:wipe`,
or reseed production. Do not roll back the Phase 15B tables after they contain security
history; use a reviewed forward fix.

Production password rotation remains a separate, explicitly approved cutover. The
authorized DILG officer—not the developer—will enter and distribute each temporary
password through an approved confidential channel.

## Next phase

Phase 15D adds normalized email-plus-IP throttling, bounded temporary lockouts, generic
login errors, lock/unlock auditing, and DILG-authorized unlocking. It remains local and
staging only until reviewed.
