# Phase 13A — Validated Barangay Polygon Detection

## Result

Phase 13A integrates the official-source Santa Cruz boundary dataset into the Laravel
and FastAPI GIS paths. Reports with GPS coordinates inside exactly one polygon are
automatically assigned to that barangay. The citizen barangay selection remains an
explicit routing declaration and fallback; nearest barangay-hall points are never used
to establish jurisdiction.

## Source and validation record

| Field | Recorded value |
|---|---|
| Source organization | Municipal Planning and Development Office (MPDO), as supplied by the project owner |
| Source artifact | `local-boundary.gpkg` (kept outside the repository) |
| Dataset version | Source file received for Phase 13A; no embedded release label was supplied |
| Validation date | 2026-09-09 (Asia/Manila) |
| Validator | CIVICLEAR reproducible import and automated acceptance suite |
| Source CRS | EPSG:3123 — PRS92 / Philippines zone 3 |
| Output CRS | EPSG:4326 — WGS 84 / RFC 7946 GeoJSON |
| Toolchain | GDAL 3.12.4 from QGIS 4.0.2 |
| Source SHA-256 | `708CAD98163E76FE35C03F166D8F193C8B57967C103992A1638D071FCD03E252` |

The GeoPackage contains one municipal MultiPolygon, 26 barangay MultiPolygons, and a
separate provincial-LGU layer. The import validation confirms 26 features, 26 unique
names, 26 valid geometries, zero empty geometries, and an exact name match against the
application's configured Santa Cruz barangays.

## Derived tracked artifacts

| Artifact | SHA-256 |
|---|---|
| `public/gis/santa_cruz_barangays.geojson` | `C3FA4C727C9DCC16DBFE19AA6D56C2CCC5397349D2B0B7DAA7AE85D29FBC959F` |
| `public/gis/santa_cruz_municipality.geojson` | `CA61A23480063B15998010A5F7A3714DE3124B99556C99B7CF6B840B52B3D4B9` |
| `tests/Fixtures/mpdo_barangay_acceptance_points.geojson` | `78F4577D12BA63326171B0834DBA8F88E94765552514D4774E0EC321DCF3E88E` |

Regenerate and revalidate these files with:

```powershell
.\scripts\import-mpdo-boundaries.ps1 -SourcePath 'C:\path\to\local-boundary.gpkg'
```

The raw GeoPackage is deliberately not copied into the application or container build
context. The repository `.dockerignore` rejects `*.gpkg` files.

## Detection and edge behavior

- Laravel and FastAPI use the same 26-feature barangay layer.
- A point inside exactly one polygon returns `auto_detected` and the barangay office.
- A point shared by two polygon boundaries returns `barangay_boundary_ambiguous` and
  requires authorized manual routing; source feature order never decides jurisdiction.
- An inside-coverage point with no barangay match returns `barangay_not_matched`.
- A point outside the union returns `outside_coverage`.
- Authorized staff verification and DILG manual routing remain enforced by existing
  role and effective-barangay rules.

## Final barangay-hall marker dataset

On 2026-09-09, the project owner supplied the final 26-row barangay-hall Excel master
and matching JSON code dataset. The two files matched exactly for PSGC, barangay,
office name, latitude, and longitude. They contained 26 unique barangays and 26 unique
PSGC values, with no missing configured barangay. Every point resolved automatically
to its identically named MPDO polygon.

| Artifact | SHA-256 |
|---|---|
| Supplied `CIVICLEAR_Final_Barangay_Halls.xlsx` | `6351EF24A246EE63D2131FDDE78963FB4755D57E128B3FAD151E2399CDD719EC` |
| Supplied `civiclear_barangay_halls_final.json` | `4BB1766A6FA444422D75D13C917ACE66BAEFD6F6AA95AEBD0F04EB85AA9F435C` |
| Derived `public/gis/barangay_halls.geojson` | `35247E7CC581D91F77ADD1C3D9D9A3F79C797F7D3608D20A69C523F0A7E63631` |

The old provisional markers and conflicting legacy coordinates were replaced. The
application now exposes 26 verified hall markers while continuing to use the MPDO
polygons—not hall proximity—for jurisdiction and report routing.

## Legacy report reconciliation

The guarded `gis:reconcile-legacy-barangays` command defaults to a read-only dry run
and requires `--apply` before it changes report records. It excludes reports that
already have a citizen-selected or staff-assigned barangay and locks each eligible
record before updating it.

On 2026-09-09, a dry run found 23 legacy reports stored with
`barangay_boundary_unavailable`. All 23 produced one unambiguous MPDO polygon match:
18 were assigned to Calios and 5 to Poblacion III. The reconciliation was applied,
each automatic assignment received a system timeline entry, and a second dry run
confirmed that no legacy boundary-unavailable reports remained.

## Acceptance evidence

The source-derived test fixture uses `ST_PointOnSurface` for every barangay rather than
invented center coordinates. All 26 points resolve to their expected names in both
Laravel and FastAPI. A known shared vertex between Jasaan and Malinao verifies the
ambiguous-boundary manual-review rule. The GIS web page also verifies that both MPDO
layers are present and rendered.

## Known limitation

Phone GPS accuracy can place a reading on the wrong side of a real-world boundary.
Polygon detection establishes the mapped jurisdiction for the recorded coordinate; it
does not prove the physical incident location. Shared-edge, low-quality, or unmatched
locations must therefore remain reviewable, and the original GPS evidence and staff
correction history must be preserved.
