# Phase 12A - Operational GIS and Official Analytics

## Result

Phase 12A separates the broad operational GIS dataset from staff-verified records
that are eligible for official municipal statistics. No production data or database
schema is changed.

## Delivered behavior

- The GIS filter can switch between **Operational reports** and **Official verified statistics**.
- Marker fill continues to show the response lifecycle status.
- Marker rings and symbols identify staff-verified, AI-pending, unverified, rejected,
  duplicate, outside-jurisdiction, and test records.
- Map filtering is performed by the authenticated server query instead of only in
  browser memory.
- The GIS workspace uses a map-first split layout with a compact floating report-status
  key and a persistent selected-report inspector.
- Filters use an aligned two-row desktop grid, map popups remain above overlays, and
  report states use accessible colored pill badges with distinct icons.
- Barangay staff remain restricted to their assigned barangay for both datasets.
- The generic `/gis-map` link sends barangay staff to their assigned workspace while
  keeping cross-barangay access forbidden.
- Official map and analytics counts use `official_violation_type` confirmed by staff.
- DILG and barangay dashboard verified/resolved totals use the same official scope.
- Operational citizen-category analytics remain available for backward compatibility
  and continue excluding the internal unclassified sentinel. The operational
  most-common summary also excludes legacy categories outside the five current
  trained image classes; no underlying report is hidden, changed, or deleted.

## Official-statistics eligibility

A report is counted only when all of these conditions are true:

- it is not test data;
- `verified_at` and `official_violation_type` are present;
- `verification_status` is `Valid Violation`;
- the report is validated inside Santa Cruz;
- the lifecycle status is not `Rejected`; and
- it is not a duplicate.

Verified reports remain counted after moving to Assigned, In Progress, Action Taken,
Resolved, or Closed.

## Files and schema

The phase changes the report model, GIS API/resource, GIS marker JavaScript and view,
DILG/barangay dashboards, DILG/barangay analytics views/controllers, roadmap, and a
dedicated feature-test file. It adds no migration and makes no destructive data change.

## Acceptance evidence

Verified locally and released on 2026-09-15 (Asia/Manila):

- Focused GIS, analytics export/profile, and compatibility tests: 30 tests, 443 assertions.
- Complete Laravel regression suite: 205 tests, 2,078 assertions.
- Vite production asset build completed successfully.
- Blade template compilation and JavaScript syntax checks passed.
- Laravel Pint passed for every changed PHP file.
- Git whitespace validation passed.

## Production release

The reviewed feature branch was merged to `main` as commit
`151b69d4f1d8e8af83937a8eff0a77bfd481e4bf`. GitHub Actions run
`34986755053` built the immutable Laravel and FastAPI images from that exact commit.

| Item | Value |
|---|---|
| Laravel image digest | `sha256:a5bdb231efeb15c5d23f7e16ac22b959751247eb37729b0b1bcf08211696b7ee` |
| Active revision | `ca-civiclear-laravel--gis151b69d` |
| Active traffic | 100 percent |
| Rollback revision | `ca-civiclear-laravel--gis13bce574f9` |
| Rollback traffic | 0 percent, retained active and healthy |

The candidate and public hostname passed health, login-branding, authentication
redirect, mobile violation-type, GIS JavaScript, 26-boundary, and 26-barangay-hall
checks. No database migration was required. The shared production database retained
33 reports and 27 accounts after the switch.

Rollback is traffic-only: assign 100 percent to
`ca-civiclear-laravel--gis13bce574f9` and zero percent to
`ca-civiclear-laravel--gis151b69d`. Do not delete either revision until authorized
staff complete the authenticated GIS acceptance check.

## Authorized test-data cleanup

After release, the owner authorized permanent removal of reports that were explicitly
marked `is_test_data = true`. A verified recovery export was created before deletion.
The exact removed set was `RCV-2026-0001` through `RCV-2026-0010`, plus
`RCV-2026-0037`: 11 report rows and 10 cascade-linked timeline rows. One referenced
private photo object was deliberately retained and its object reference remains in the
recovery export. Backup SHA-256:
`8ababb841b9899911674aaad62612294a02d0c4869253537717c83707770b4e8`.

Post-cleanup verification found 22 non-test reports, zero marked test reports, and zero
orphan timeline rows. No non-test report, account, GIS feature, or application file was
deleted or modified by this cleanup.
