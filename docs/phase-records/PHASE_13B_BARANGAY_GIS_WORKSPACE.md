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

- Focused GIS and navigation suite: 15 tests, 462 assertions, including all 26 barangay accounts.
- Complete Laravel regression suite: 196 tests, 2,019 assertions.
- Production Vite build completed successfully.
- Blade views compiled successfully.
- JavaScript syntax and Git whitespace checks passed.

## Production release

Phase 13B was released to Azure Container Apps on 2026-09-15 (Asia/Manila) from
application commit `ce574f9381391b68842b987e77fa735cb716f7d2`.

| Item | Value |
|---|---|
| Laravel image digest | `sha256:c952e7e0651cb39258a6e34bec2266891fc2c8466467357e3ba33cc49d88ef95` |
| Active revision | `ca-civiclear-laravel--gis13bce574f9` |
| Active traffic | 100 percent |
| Rollback revision | `ca-civiclear-laravel--gis28c3743` |
| Rollback traffic | 0 percent, retained active and healthy |

The release required no database migration. Before the traffic switch, the candidate
revision passed health, login, authentication redirect, GIS boundary, barangay-hall,
GIS JavaScript, mobile barangay, and mobile violation-type checks. After the switch,
the same public checks passed and an invalid opaque tracking credential returned the
expected HTTP 404 after a database lookup. Container logs showed no application error.

Rollback is a traffic-only operation: route 100 percent to
`ca-civiclear-laravel--gis28c3743` and zero percent to
`ca-civiclear-laravel--gis13bce574f9`. The owner subsequently completed the
authenticated DILG and barangay GIS visual acceptance check on 2026-09-15. A newer
Phase 12A GIS refinement revision now serves production; its evidence and current
rollback target are recorded in the Phase 12A record.
