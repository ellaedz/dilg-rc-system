# Phase 15 Azure preview release record

**Date:** 2026-10-03 (Asia/Manila)  
**State:** Azure revision promoted after owner approval; production traffic 100 percent

## Reviewed source and build

- PR: https://github.com/ellaedz/dilg-rc-system/pull/8
- Merge commit: `790779c7a4caa386f534aefc1fdc529fc6344599`
- Phase 10B build: https://github.com/ellaedz/dilg-rc-system/actions/runs/37090566797 (success)
- Laravel image: `crcivicleara41a250a.azurecr.io/civiclear-laravel@sha256:dcec9022bedb0bbeb0abbc22554fcf368f29c2e4e02d3973ef6dac73bb4f6b4a`
- Previous production revision: `ca-civiclear-laravel--web-1639148`
- New preview revision: `ca-civiclear-laravel--sec15-790779c7` (healthy)
- Preview label: `phase15-preview`; after promotion, the labeled revision receives
  100 percent of normal traffic
- Preview URL: https://ca-civiclear-laravel---phase15-preview.livelysea-7e585be2.eastasia.azurecontainerapps.io/login

## Recovery backup

The fresh PostgreSQL custom-format backup is outside the repository at:

`C:\Users\63923\Desktop\database\civiclear-phase15-preview-backup-20261003-104844\civiclear-postgres.dump`

- Format header: `PGDMP`
- Size: 84,942 bytes
- SHA-256: `3468c7b033c86a7bccdee7e965530b93e71aae393650f975ba614bfa3cc69f7a`
- PostgreSQL tools: version 17.3
- `pg_restore --list` catalog verification: passed, 136 lines
- The backup contains production data. Keep it outside GitHub and public artifacts.

## Production data and migration checks

Before migration, a read-only transaction counted 27 users, 26 barangay staff,
43 reports, 52 timeline rows, and 23 migrations.

`2026_10_01_000001_add_password_security_foundation.php` ran once inside the
zero-traffic Azure revision. A postmigration read-only transaction counted the
same users, barangay staff, reports, and timelines, plus 24 migrations. All
27 accounts remain active. Zero accounts require a password change; password
history and security audit tables are empty. All 26 barangay assignments remain
distinct. No production password was reset or rotated.

The preview and production revisions use the same production PostgreSQL database.
For this preview checkpoint, inspect the UI and sign in only with the correct
authorized account. Do not submit password reset, change-password, lockout, or
unlock tests through this URL. Those mutations would affect production accounts.
Use the previously verified isolated local preview for mutating workflow tests.

## HTTP and traffic checks

- Preview `/`, `/login`, `/up`, and `/api/mobile/violation-types`: HTTP 200.
- Unauthenticated preview `/account-management` and `/change-password`: redirect
  to the preview `/login` host.
- Unauthenticated GIS and CSV/PDF export routes: redirect to login.
- Production `/login`: HTTP 200 after migration and preview label assignment.
- Before owner approval, traffic was 100 percent to `web-1639148` and zero
  percent to `sec15-790779c7`.
- After owner approval, traffic is 100 percent to `sec15-790779c7`.
  `web-1639148` remains active and healthy at zero percent for rollback.
- Live `/`, `/login`, `/up`, and `/api/mobile/violation-types`: HTTP 200.
- Live unauthenticated `/account-management`, `/change-password`, GIS, and
  CSV/PDF export routes: HTTP 302 to the live login host.
- Postcutover read-only database checks: 27 users, 26 barangay staff, 43 reports,
  52 timelines, zero forced changes, and zero current account locks.
- Authenticated GIS, report, export, and mobile submission checks remain pending
  authorized user review; no production report was created for this smoke test.

## Rollback procedure before password cutover

The additive migration is compatible with the old revision. Preserve its new
tables and columns; do not run `migrate:rollback`, `migrate:fresh`, `db:wipe`, or
seeders on production.

If the preview itself is faulty while normal traffic remains on the old revision,
remove its label and deactivate only `sec15-790779c7` after confirming the old
revision is still healthy. These commands no longer apply directly after the
production traffic switch; use the traffic rollback command below first:

```powershell
az containerapp revision label remove -n ca-civiclear-laravel -g rg-civiclear-phase10b --label phase15-preview
az containerapp revision deactivate -n ca-civiclear-laravel -g rg-civiclear-phase10b --revision ca-civiclear-laravel--sec15-790779c7
```

If production traffic is later moved and the new application fails, route 100
percent back to the verified previous revision, then verify `/login`, `/up`,
reports, and the account inventory:

```powershell
az containerapp ingress traffic set -n ca-civiclear-laravel -g rg-civiclear-phase10b --revision-weight ca-civiclear-laravel--web-1639148=100
```

Do not restore the database backup over live writes without a separately reviewed
recovery plan. After an authorized password cutover, application traffic rollback
does not reverse passwords. Preserve any new password histories and audit events.

## Remaining release gates

1. Authorized DILG user verifies the live Account Management page and Change
   Password link, plus reports, GIS, analytics, and CSV/PDF exports.
2. Confirm the mobile API through an authorized mobile check when practical.
3. Obtain separate explicit approval before rotating production credentials.
