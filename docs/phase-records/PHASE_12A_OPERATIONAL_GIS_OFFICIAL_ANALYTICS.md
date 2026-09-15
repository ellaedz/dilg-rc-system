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
- Barangay staff remain restricted to their assigned barangay for both datasets.
- Official map and analytics counts use `official_violation_type` confirmed by staff.
- DILG and barangay dashboard verified/resolved totals use the same official scope.
- Operational citizen-category analytics remain available for backward compatibility
  and continue excluding the internal unclassified sentinel.

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

Verified locally on 2026-09-15 (Asia/Manila):

- Focused GIS, analytics export/profile, and compatibility tests: 30 tests, 443 assertions.
- Complete Laravel regression suite: 203 tests, 2,061 assertions.
- Vite production asset build completed successfully.
- Blade template compilation and JavaScript syntax checks passed.
- Laravel Pint passed for every changed PHP file.
- Git whitespace validation passed.

## Release state

The work is on `feature/phase-12a-gis-official-analytics`. It has not been deployed to
Azure. The existing production revision and all reports, photos, GIS data, AI results,
timelines, and account assignments remain unchanged.
