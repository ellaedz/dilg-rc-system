# Phase 15B - Password and Audit Data Foundation

## Status

Complete locally on `feature/phase-15b-password-data-foundation`.

This phase is based on the verified Phase 15A baseline commit `a7ca241`. It adds only
the backward-compatible data and application foundation needed by the later account
management and login-protection phases.

No production migration was run. No live password, account, session, report, photo,
GIS record, AI result, timeline, or barangay assignment was changed.

## Additive database changes

Migration `2026_10_01_000001_add_password_security_foundation.php` adds these fields to
`users`:

- `is_active`, default `true`;
- `must_change_password`, default `false`;
- nullable `password_changed_at`;
- `failed_login_attempts`, default `0`;
- nullable `last_failed_login_at`;
- nullable `locked_until`; and
- `session_version`, default `1`.

The defaults preserve existing access after migration: users are active, not locked,
and not forced to change their password. The migration contains no update to the
existing `password` column and performs no credential rotation.

The migration also creates:

- `password_histories`, containing only a user reference, a one-way password hash, and
  its creation time; and
- `security_audit_events`, containing the actor, affected account, typed action,
  timestamp, IP address, and a bounded user-agent value.

The migration is reversible. Its rollback removes only the new Phase 15B tables and
columns; it does not drop users, reports, or other production records.

## Password policy foundation

`CiviclearPasswordPolicy` centralizes these rules for future reset and change forms:

- 16-character minimum and 128-character maximum;
- privacy-preserving compromised-password verification through Laravel's
  k-anonymity-based verifier;
- a local SHA-256 blocklist for predictable CIVICLEAR-specific/common values;
- rejection of the current password; and
- rejection of the five most recently retained password hashes.

The repository stores no plaintext blocklist values. Tests create random in-memory
values and do not print or persist plaintext credentials. If the external compromised
password service is temporarily unavailable, Laravel's verifier does not make the
application unusable; the local blocklist and all other rules remain active.

## Audit foundation

The typed audit actions cover password resets, password changes, forced-change state,
failed logins, account locking, and account unlocking. `SecurityAuditLogger` accepts no
metadata or arbitrary payload, so a controller cannot accidentally pass password form
fields into the audit table.

Password hashes are hidden by the password-history model. Login counters, lock state,
and session-version state are hidden from normal user serialization.

## Dependency security maintenance

The security check found four advisories in previously locked packages. Only the four
affected framework/storage/parser packages were updated:

- `laravel/framework` 12.62.0 to 12.69.3;
- `league/commonmark` 2.10.1 to 2.10.3;
- `league/flysystem` 3.35.2 to 3.36.0; and
- `league/flysystem-local` 3.31.0 to 3.35.3.

No application dependency constraint was broadened. The final Composer audit reports
no known security advisories.

## Verification

- Phase 15B focused tests: 8 passed, 29 assertions.
- Complete Laravel suite after the dependency patches: 217 passed, 2,145 assertions.
- Laravel Pint on Phase 15B PHP files: passed.
- `composer validate --strict --no-check-publish`: passed.
- `composer audit --no-interaction`: no known advisories.
- `git diff --check`: passed.

The complete suite also verifies report/GIS scoping, public data minimization, mobile
submission contracts, PDF/CSV exports, AI integration, private photo handling, and the
existing DILG/barangay authorization behavior.

## Files owned by this phase

- `.env.example`
- `app/Enums/SecurityAuditAction.php`
- `app/Models/PasswordHistory.php`
- `app/Models/SecurityAuditEvent.php`
- `app/Models/User.php`
- `app/Rules/NotBlockedPassword.php`
- `app/Rules/NotCurrentPassword.php`
- `app/Rules/NotRecentlyUsedPassword.php`
- `app/Services/CiviclearPasswordPolicy.php`
- `app/Services/SecurityAuditLogger.php`
- `composer.lock`
- `config/security.php`
- `database/migrations/2026_10_01_000001_add_password_security_foundation.php`
- `tests/Feature/Phase15BPasswordDataFoundationTest.php`
- `CIVICLEAR_SECURITY_IDENTITY_AI_ROADMAP.md`
- `docs/phase-records/PHASE_15B_PASSWORD_DATA_FOUNDATION.md`

## Deployment and rollback boundary

This phase is not authorization to deploy or rotate credentials. Before a later
production migration, use the verified Phase 15A backup procedure, create an immutable
revision, validate the migration in an isolated environment, and record pre/post data
counts.

If the additive migration must be rolled back before any Phase 15C password operation,
run only this migration's `down` path in the isolated or approved maintenance context,
then redeploy the prior immutable revision. Do not use `migrate:fresh`, `db:wipe`,
reseeding, or any destructive database reset. Once Phase 15C begins retaining password
history or audit events, preserve those records and use a reviewed forward fix instead
of casually rolling the migration back.

## Next phase

Phase 15C will implement DILG-only account management, administrator-entered temporary
password resets, forced first-login replacement, self-service password changes, and
session invalidation. It must remain local/staging-only until the complete security
flow is reviewed and production cutover is explicitly approved.
