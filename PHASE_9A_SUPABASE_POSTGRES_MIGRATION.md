# CIVICLEAR Phase 9A — Supabase PostgreSQL Migration

## Current status

Phase 9A has passed disposable-schema validation, final import, read-only cutover,
controlled PostgreSQL write validation, and final runtime activation.

- Branch: `feature/phase-9a-supabase-postgres-migration`
- Approved starting commit: `e5242e5ff22a45a2adf1ffa93f2306beb7a4d1c7`
- Validation date: 2026-08-08
- Current Laravel database: Supabase PostgreSQL with application writes enabled
- Laravel maintenance mode: inactive
- Final SQLite backup: created and verified outside the repository
- Final PostgreSQL schema: created, migrated, imported, and parity-verified
- PostgreSQL writes from the application: enabled after explicit Gate 3B approval
- Supabase Auth, Data API, Storage, and mobile SDK: not introduced

The technical migration gates are complete. The application is intentionally not left
running by this implementation session; normal Laravel startup is still required when
the user wants to use the web or mobile API.

## Implemented safeguards

- PHP `pdo_pgsql` support is enabled and verified.
- PostgreSQL 17 client tools are available outside the repository.
- Supabase Session Pooler credentials and the CA certificate remain outside Git.
- TLS uses `verify-full`; the disposable validation observed TLS 1.3.
- `DB_URL` overrides are rejected by the Phase 9A guard.
- Runtime PostgreSQL defaults to the private `civiclear` search path.
- Test operations require `APP_ENV=testing`.
- Disposable schemas must match
  `civiclear_phase9a_test_[A-Za-z0-9_]+`.
- Protected Supabase and application schemas are denied by name.
- The expected database name, selected schema, CA file, and active TLS connection are
  verified before a guarded operation proceeds.
- PHPUnit PostgreSQL runs require the dedicated `phase9a_pgsql` connection.
- PHPUnit sessions are explicitly released to stay within the free Session Pooler
  connection limit.
- There is no automatic migration command in container or application startup.
- The final SQLite backup command accepts only the configured live CIVICLEAR SQLite
  database, requires maintenance mode and the exact approval sentinel, writes only to
  a new absolute directory outside the repository, and detects concurrent commits.
- Final-schema preparation has a read-only dry-run. Its write mode requires the exact
  `civiclear` schema, maintenance mode, the approval sentinel, TLS verification, an
  advisory lock, and an empty or absent schema owned by the active migration role.
- Final-schema preparation never runs `migrate:fresh`, drops or resets a schema, or
  imports SQLite rows.
- Final import and final parity verification require the exact active schema and
  reject PUBLIC, `anon`, `authenticated`, or `service_role` privileges.

The intended final `civiclear` schema must remain outside Supabase Data API exposed
schemas. Do not grant `anon`, `authenticated`, or `service_role` USAGE or object
privileges on it unless separately required and approved.

## SQLite safety snapshot

A pre-implementation SQLite safety snapshot was created with SQLite `VACUUM INTO`,
not an unsafe live-file copy. It and its manifest are stored outside Git under the
Phase 9A backup directory.

- SHA-256:
  `e6d16cc2f97cf40c0b0ff4b96dc366af84311048afca08f1f32bd68451fae5bc`
- SQLite `integrity_check`: passed
- SQLite `foreign_key_check`: passed
- Source and backup table counts: matched
- Snapshot immutability was checked before and after test import

This is a pre-implementation recovery snapshot only. It is not the final migration
source. Final import still requires writes to be frozen and a fresh, immutable SQLite
backup and manifest to be produced after explicit approval.

## Final frozen SQLite backup

Gate 1A was explicitly approved and completed on 2026-08-08. Laravel remains in
maintenance mode, no PHP queue/scheduler/server process was found during the freeze,
and the final backup files were marked read-only.

- Backup directory:
  `C:\Users\63923\CIVICLEAR_BACKUPS\phase9a-final-20260808-174756`
- SQLite source SHA-256:
  `e3811511a0cfdfe2b312c4f3ec7f6f3457d3bbd43c52fc1828e34a1bce816b9c`
- Final backup SHA-256:
  `e6d16cc2f97cf40c0b0ff4b96dc366af84311048afca08f1f32bd68451fae5bc`
- Verified tables: 14
- SQLite `integrity_check`: passed
- SQLite `foreign_key_check`: passed with zero violations
- Manifest and actual backup hash: matched
- Source and manifest source hash: matched
- Final PostgreSQL schema creation/import: completed after separate approvals

This exact backup and manifest are now the only approved candidate source for the
final Phase 9A import. Do not recreate, edit, rename, or replace either file without
restarting the final-backup approval gate.

## Final PostgreSQL import evidence

Gate 1B and Gate 1C were separately approved. The user confirmed that the guarded
commands returned the expected successful results while Laravel remained in
maintenance mode:

- final schema: `civiclear`
- repository migrations applied: 19
- imported application/operational tables: 13
- imported rows: 91
- imported backup SHA-256:
  `e6d16cc2f97cf40c0b0ff4b96dc366af84311048afca08f1f32bd68451fae5bc`
- canonical table counts matched: 13
- canonical table digests matched: 13
- PostgreSQL indexes found: 50
- PostgreSQL foreign keys found: 3
- operational-table policy: `preserve`
- Laravel runtime cutover: completed
- PostgreSQL application writes: enabled after controlled validation

## PostgreSQL compatibility work

Phase 9A added guarded commands for:

- validating all migrations inside a disposable PostgreSQL schema;
- importing an approved immutable SQLite snapshot transactionally; and
- performing read-only canonical parity verification;
- inspecting or creating the final SQLite backup after a separate approval; and
- inspecting or preparing the final PostgreSQL schema without any drop/reset path.

The Gate 1 safety-tool corrections are implemented, but none of their final write
modes have been executed. The safe command boundaries are:

- `phase9a:create-final-sqlite-backup --dry-run` performs SQLite inspection only;
- `phase9a:prepare-final-postgres --dry-run` performs PostgreSQL inspection only;
- omitting `--dry-run` requires maintenance mode and the exact approval sentinel;
- final import additionally requires the explicit operational-table policy
  `preserve`; and
- `phase9a:verify-import --mode=final` performs the guarded read-only parity check
  after an authorized final import.

Compatibility corrections include:

- parameterized legacy migration updates instead of SQLite-specific quoted literals;
- correct handling of the numeric `report_timelines.updated_by` foreign key;
- PostgreSQL-only numeric precision widening to preserve existing SQLite GPS,
  confidence, and response-time values without rounding;
- bound dashboard and analytics status values instead of double-quoted SQL strings;
- PostgreSQL and SQLite implementations for monthly date grouping;
- portable aggregate `HAVING` expressions;
- savepoint isolation around tests that intentionally violate unique constraints; and
- explicit Session Pooler connection cleanup during test teardown.

No mobile flow, FastAPI behavior, photograph storage, tracking contract, official
verification workflow, GIS routing rule, or application branding was changed.

## Trial migration evidence

The disposable migration schema passed all 19 repository migrations.

The approved snapshot was then imported into the guarded disposable schema:

- imported tables: 13
- imported rows: 91
- verified source SHA-256: matched the approved manifest
- canonical table counts matched: 13
- canonical table digests matched: 13
- PostgreSQL indexes found: 50
- PostgreSQL foreign keys found: 3
- Report Number uniqueness: passed
- Tracking-token hash uniqueness: passed
- Idempotency-key hash uniqueness: passed
- Token-derivation nonce uniqueness: passed
- Report timeline relationship checks: passed
- Verifier relationship checks: passed
- Boolean, JSON, numeric, date, index, and foreign-key checks: passed

Canonical verification treats a Laravel `date` column as a calendar date. Legacy
SQLite rows sometimes store a time suffix in these declared date fields; PostgreSQL
correctly normalizes them to `YYYY-MM-DD`. Real timestamp columns remain independently
verified as timestamps.

## Operational-table disposition

The trial importer preserved operational tables because no approval was given to drop
them silently:

| Table | Snapshot rows | Trial action |
| --- | ---: | --- |
| `cache` | 4 | Preserved |
| `cache_locks` | 0 | Preserved |
| `jobs` | 0 | Preserved |
| `job_batches` | 0 | Preserved |
| `failed_jobs` | 0 | Preserved |
| `sessions` | 1 | Preserved |
| `password_reset_tokens` | 0 | Preserved |

Before final import, the user must explicitly confirm the operational-table policy.
The guarded importer currently accepts `preserve` only; it does not silently clear
cache or session rows. Durable users, password hashes, roles, reports, timelines,
security hashes, identifiers, and known `is_test_data` provenance must be preserved.

## Regression evidence

PostgreSQL Session Pooler testing was serial and did not use PHPUnit parallel mode.

- Unit suite: 23 passed, 111 assertions.
- Feature suite: 81 tests passed during the complete run; the only failed test was an
  SQLite-specific transaction assumption in an intentional uniqueness violation.
- Corrected Phase 8B security suite: 9 passed, 73 assertions on PostgreSQL.
- Navigation smoke suite passed on PostgreSQL after the SQL portability audit.
- Final local SQLite unit suite: 23 passed, 111 assertions.
- Final local SQLite feature suite: 94 passed, 692 assertions after the Gate 2
  read-only guard was added.
- Phase 9A local safety tests: 22 passed, 44 assertions after the Gate 2 read-only
  guard was added.

Together, every current unit and feature test has passed against the intended code.
The split feature evidence is retained honestly rather than claiming a single green
run that was not performed after the final test-only savepoint correction.

## Remaining approval gates

### Gate 1 — Final import

Do not proceed without explicit approval. After approval:

1. Completed: Laravel is in maintenance mode and SQLite remains active.
2. Completed: application and queue writes are frozen.
3. Completed: a fresh SQLite `VACUUM INTO` backup exists outside Git.
4. Completed: the manifest and SHA-256 were produced and independently verified.
5. Completed: the private final `civiclear` schema was created with restricted
   privileges.
6. Completed: migrations and the exact frozen backup were imported.
7. Completed: table-count, canonical-digest, relationship, identifier, and hash
   checks passed.
8. Completed safeguard: the application remained pointed at SQLite until Gate 2 was
   separately approved.

### Gate 2 — Database cutover with writes disabled

Do not proceed without a second explicit approval. Switch Laravel configuration to
PostgreSQL while application writes remain disabled. Verify health, staff login,
roles, dashboards, report detail, tracking, GIS views, and read-only database parity.

Gate 2 was approved. Before changing runtime configuration, the ignored SQLite `.env`
was copied to the following external read-only rollback file and verified by SHA-256:

`C:\Users\63923\CIVICLEAR_BACKUPS\phase9a-gate2-env-rollback-20260808-184703\.env.sqlite.rollback`

The Gate 2 implementation adds two independent database-write barriers while
`PHASE9A_RUNTIME_READ_ONLY=true`:

- PostgreSQL `default_transaction_read_only=on` is verified for the active session;
- Laravel permits only single `SELECT` or `SHOW` statements and rechecks the server
  session before every framework-managed query.

The cutover verifier also refuses database-backed session, cache, or queue drivers,
repeats canonical parity against the immutable SQLite backup, verifies private schema
privileges, and requires maintenance mode. The runtime `.env` cutover and its external
acceptance run passed.

Gate 2 acceptance evidence:

- independent runtime driver/schema check: `pgsql` / `civiclear`
- PostgreSQL session read-only state: enabled
- framework SQL write guard: enabled
- immutable-backup table counts and canonical digests: 13/13 matched
- indexes and foreign keys: 50 / 3
- imported staff role groups: 2
- opaque public tracking lookup: passed without printing the raw token
- DILG staff login and logout: manually passed
- dashboard, report list/detail, Performance, Analytics, GIS Map, and Profile:
  manually passed
- unauthenticated root, login, and mobile API during acceptance: HTTP 503
- temporary maintenance bypass: removed
- temporary Laravel acceptance server: stopped
- maintenance mode at the end of Gate 2: active

### Gate 3 — Enable PostgreSQL writes

Do not proceed without a third explicit approval. Enable writes only after the
no-write acceptance checks pass and a rollback decision point is recorded.

Gate 3A was separately approved and passed under maintenance mode. A process-only
override disabled the read-only guard for one idempotent validation command; the
persistent `.env` remained read-only until Gate 3B approval.

- controlled Report Number: `RCV-2026-0028`
- `is_test_data`: `true`
- verification status: `Pending`
- official violation type: `null`
- timeline rows: 1
- opaque public tracking lookup: passed
- raw tracking token printed or persisted: no
- idempotent validation command exit code: 0
- persistent Laravel read-only guard immediately after validation: enabled
- PostgreSQL session read-only state immediately after validation: `on`
- maintenance mode immediately after validation: active

This is the first intentional PostgreSQL-only row. The frozen SQLite backup remains
the recovery baseline, but SQLite is no longer a current rollback target unless this
PostgreSQL delta and all later PostgreSQL writes are explicitly reconciled.

Gate 3B was explicitly approved and completed on 2026-08-08:

- a hash-matched, read-only copy of the pre-activation PostgreSQL `.env` was stored at
  `C:\Users\63923\CIVICLEAR_BACKUPS\phase9a-gate3b-readonly-env-20260808-194911\.env.pgsql-readonly.rollback`;
- `PHASE9A_RUNTIME_READ_ONLY` was changed to `false`;
- database-backed sessions, cache, and queues were restored;
- the application connection verified `pgsql`, schema `civiclear`, TLS active, and
  `default_transaction_read_only = off`;
- the Gate 3A test report remained present and marked `is_test_data = true`;
- maintenance mode was lifted;
- local HTTP checks passed: `/up` 200, `/` 302 to login, `/login` 200, and an invalid
  opaque tracking credential returned 404;
- the temporary validation server was stopped and no CIVICLEAR listener was left
  running.

## Rollback and recovery

Before PostgreSQL writes are enabled, rollback is intentionally simple:

1. Keep the final immutable SQLite backup unchanged.
2. Restore Laravel's SQLite connection configuration.
3. Clear Laravel configuration cache.
4. Confirm the selected SQLite path and hash before lifting maintenance mode.
5. Run read-only health, login, dashboard, report, and tracking checks.

If PostgreSQL has accepted any production writes, never switch back blindly. Freeze
writes first, export and reconcile the PostgreSQL-only delta, verify identifiers and
relationships, and obtain a separate rollback approval. Phase 9A does not implement
dual writes.

No SQLite database or backup may be deleted during Phase 9A.
