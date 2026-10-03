# Phase 15A - Security Baseline and Recovery Preparation

## Status

**COMPLETE - Supabase resumed, live service verified, and recovery backup created**

No password, account, session, database row, storage object, Azure secret, traffic
weight, or deployed revision was changed during this baseline.

## Repository baseline

| Item | Observed value |
| --- | --- |
| Baseline date | 2026-10-01 (Asia/Manila) |
| Phase branch | `chore/phase-15a-security-baseline` |
| Starting remote branch | `origin/main` |
| Starting commit | `ea84b4d1d481b842a1496c12cf9fec342f45551f` |
| Planned phase commit | `docs: establish phase 15a security baseline` |
| Application merge in starting history | `163914860dcce530aed71c7ad68d2fbb9cce32ac` |

The latest remote commit, `ea84b4d`, changes only `README.md` but removes 1,138 lines
and adds two lines. This unexpected documentation rewrite is recorded for later review;
it was not silently corrected or reverted during Phase 15A.

The pre-existing untracked APKs, mobile image assets, `output/`, and `tmp/` belong to
the user and were preserved. The new security/identity/AI roadmap is an intended phase
documentation artifact.

## Toolchain baseline

| Tool | Observed version/state |
| --- | --- |
| PHP | 8.2.12 |
| Composer | 2.10.1 |
| Laravel framework | 12.62.0 |
| PHPUnit | 11.5.55 |
| Node.js | 22.21.0 |
| npm | 11.14.1 |
| Python | 3.12.5 |
| Java | Microsoft OpenJDK 17.0.20 LTS |
| Gradle wrapper | 8.14.3 |
| Git | 2.49.0.windows.1 |
| Azure CLI | 2.89.1 |
| Android SDK / ADB | Not available through the current shell environment |

The FastAPI virtual environment exists under `ai-inference-server/.venv`; the global
Python installation does not include pytest.

## Live Azure baseline

Read-only Azure inspection recorded:

| Item | Observed value |
| --- | --- |
| Resource group | `rg-civiclear-phase10b` |
| Application | `ca-civiclear-laravel` |
| Active revision | `ca-civiclear-laravel--web-1639148` |
| Traffic | 100 percent to `web-1639148` |
| Azure provisioning state | Succeeded |
| Azure revision health | Healthy |
| Revision mode | Multiple |
| Immediate rollback revision | `ca-civiclear-laravel--web-e254a04` at zero traffic |

Several older healthy zero-traffic revisions remain active and scaled to zero. No
traffic weights or revision activation states were changed.

## Production outage and recovery observed by the baseline

At the time of inspection:

| Probe | Result |
| --- | --- |
| `GET /` | HTTP 500 |
| `GET /login` | HTTP 500 |
| `GET /up` | HTTP 200 |
| Supabase Storage project endpoint | HTTP 540 |
| PostgreSQL shared-pooler connection | Tenant/user not found |

Supabase documents HTTP 540 as **project paused**. The configured database username
and Storage endpoint use matching project references, so the observed database error is
consistent with the paused project rather than evidence that the password is wrong.

This also exposes a health-check gap: `/up` reports success without proving that the
database and database-backed session store are available. The health endpoint must not
be described as production readiness until a separate reviewed change adds dependency
readiness checks.

The project owner resumed Supabase on 2026-10-01. The repeated probes then returned:

| Probe | Recovered result |
| --- | --- |
| `GET /` | HTTP 200 |
| `GET /login` | HTTP 200 |
| `GET /up` | HTTP 200 |
| Supabase Storage S3 endpoint without credentials | HTTP 403, expected private denial |
| PostgreSQL read-only transaction | Connected with `transaction_read_only = on` |

No database password reset or Azure configuration change was required.

## Production database inventory

The inventory ran inside an explicit read-only PostgreSQL transaction:

| Record | Count |
| --- | ---: |
| Users | 27 |
| DILG administrators | 1 |
| Barangay staff | 26 |
| Violation reports | 43 |
| Report timelines | 52 |
| Migrations | 23 |
| Database sessions | 15 |

All 26 barangay staff rows have a non-null assignment. Their sorted assignments exactly
match the 26 names in `config/santa_cruz_barangays.php`; there are no missing or
unexpected assignments. There are no case-insensitive duplicate account emails.

All 27 accounts contain bcrypt hashes and no password field is missing. There are 27
distinct stored hashes. Because secure password hashes are salted, this does not prove
that the underlying plaintext passwords are different. Controlled rotation remains a
later phase.

## Verified PostgreSQL recovery backup

A fresh custom-format logical backup was created outside the repository after Supabase
recovery:

```text
C:\Users\63923\Desktop\database\civiclear-phase15a-backup-20261001-211132\
```

| Item | Verified value |
| --- | --- |
| Dump file | `civiclear-postgres.dump` |
| Manifest | `manifest.json` |
| Format header | `PGDMP` |
| Size | 84,589 bytes |
| SHA-256 | `00b6f3eb5efc9bb272d702ab70ae20fa2c6441c061badf03b49e6469184a710c` |
| Tool | PostgreSQL `pg_dump` 17.3 |
| Catalog verification | Passed with PostgreSQL `pg_restore` 17.3 |
| Inventory before/after | Identical |

An independent second verification confirmed the dump header, file size, SHA-256, and
catalog contents against the manifest. The backup contains sensitive production data
and remains outside Git. It must not be uploaded to GitHub or shared as a normal project
artifact.

## Authentication implementation baseline

### Existing behavior

- Login validates email and password, calls Laravel `Auth::attempt`, and regenerates
  the session after successful authentication.
- Logout invalidates the current session and regenerates the CSRF token.
- Failed login returns a generic credentials error.
- The web guard uses Laravel's session driver and Eloquent user provider.
- Production uses database-backed sessions, a 120-minute idle lifetime, HTTPS-only
  cookies, HTTP-only cookies by default, and SameSite `lax` by default.
- `DilgAdminMiddleware` protects DILG-only routes.
- `BarangayStaffMiddleware` enforces the assigned barangay while allowing DILG
  monitoring access.
- User passwords use Laravel's `hashed` model cast and are hidden from serialization.
- The login page's Forgot Password action currently instructs users to contact the
  CIVICLEAR administrator and does not claim to send a reset link.

### Missing behavior required by later phases

- no normalized-email login handling;
- no email-plus-IP login throttling;
- no temporary account lockout;
- no forced password-change state;
- no self-service Change Password page;
- no DILG account-management page;
- no password history or compromised-password blocklist;
- no cross-session invalidation after password changes;
- no password reset/change/lock/unlock security audit trail; and
- no middleware preventing dashboard access while a password change is required.

### Seeder risk

`Database\\Seeders\\UserSeeder` calls `User::truncate()` and assigns the same configured
password hash to the administrator and every barangay account. It also accepts a
12-character minimum, below the new 16-character policy.

This seeder must never be used for the security upgrade or against production. Later
phases must use additive migrations and the protected administrator workflow instead.

## Test isolation evidence

`phpunit.xml` forces:

```text
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
REPORT_PHOTO_STORAGE_DRIVER=local
```

There is no `.env.testing` or `phpunit.xml.dist` overriding this contract.
`tests/TestCase.php` also rejects unsafe PostgreSQL test connections. The regression
suite therefore cannot connect to the paused production database or private production
Storage.

## Baseline verification results

| Verification | Result |
| --- | --- |
| Laravel PHPUnit | 209 passed, 2,116 assertions |
| Mobile TypeScript | Passed |
| Mobile Jest | 3 suites, 19 tests passed |
| FastAPI pytest | 39 passed |
| Composer validation | Passed |

FastAPI emitted existing dependency deprecation warnings and a pytest cache permission
warning. These warnings did not fail the 39 tests and are not authentication changes.

## Rollback preparation

Phase 15A made no application or production-state change, so no rollback is currently
required.

For later phases:

1. create and verify a fresh PostgreSQL backup before migrations;
2. record pre-deployment user, report, timeline, migration, and storage inventories;
3. deploy a new immutable Azure revision at zero traffic;
4. run migrations exactly once through an approved controlled step;
5. verify authentication and regression behavior before moving production traffic;
6. retain `web-1639148` or the then-current verified revision at zero traffic;
7. on application failure, return traffic to the verified revision;
8. do not roll back a database migration destructively when new production writes may
   exist—freeze writes, reconcile data, and use the verified backup procedure; and
9. keep password cutover separate, because returning application traffic cannot recover
   passwords that authorized staff have already changed.

Rolling Azure traffic to an older revision would not have repaired the observed outage
because all revisions depended on the same paused Supabase project. Resuming Supabase
restored the current revision without an Azure rollback.

## Phase 15A exit-gate state

- [x] Worktree ownership and starting commit are recorded.
- [x] Authentication, sessions, middleware, and authorization are inspected.
- [x] Test-database isolation is proven.
- [x] Local Laravel, mobile, and FastAPI baselines are recorded.
- [x] Live Azure revision and rollback state are recorded.
- [x] Application and database rollback requirements are documented.
- [x] Supabase project is resumed and live probes pass.
- [x] Fresh production database backup is created and verified.
- [x] Production user, role, and barangay assignments are inventoried.
- [x] Existing account records, password hashes, and role assignments are verified.

Phase 15A is complete. No plaintext password was requested or inspected, and no
production credential was changed. Phase 15B may begin from this recorded baseline.
