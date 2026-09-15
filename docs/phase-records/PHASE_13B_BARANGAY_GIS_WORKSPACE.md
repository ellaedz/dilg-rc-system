# Phase 13B - Barangay GIS Workspace

## Result

Phase 13B adds a GIS workspace to every barangay staff portal while preserving the
municipality-wide DILG map. Barangay staff receive only reports and the validated
barangay-hall marker for their assigned barangay. The server enforces that scope; it
does not rely on a hidden or disabled browser control.

## Delivered behavior

- Barangay staff can open **GIS Map** from their portal navigation.
- The assigned MPDO polygon is highlighted; neighboring polygons are muted context.
- Report markers are restricted to the authenticated account's assigned barangay.
- The validated barangay-hall marker is restricted to the assigned barangay.
- Filters cover violation class, report status, start date, and end date.
- Hotspot totals use the same server-side authorization and filters as the map.
- Report popups link to the exact authorized report detail page.
- DILG administrators retain the existing municipality-wide map and all 26 offices.
- Desktop and mobile-width layouts are responsive.

## Security and data preservation

No report, photo, AI result, GIS boundary, barangay assignment, timeline, account, or
export record is changed by this phase. Attempts by barangay staff to request another
barangay through a route or API query return HTTP 403.

## Acceptance evidence

Verified on 2026-09-15 (Asia/Manila):

- Focused GIS and navigation suite: 14 tests, 332 assertions.
- Complete Laravel regression suite: 195 tests, 1,889 assertions.
- Production Vite build completed successfully.
- Blade views compiled successfully.
- JavaScript syntax and Git whitespace checks passed.

The live Azure revision must be deployed and smoke-tested separately before Phase 13B
is considered released to production.
