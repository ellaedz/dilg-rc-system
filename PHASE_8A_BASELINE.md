# Phase 8A Baseline, Backup, and Architecture Freeze

## Status

Phase 8A records the pre-refactor CIVICLEAR state. It changes documentation only.

- Date: 2026-07-28
- Time zone: Asia/Manila
- Starting branch: `main`
- Complete starting commit: `f2f2765a8a025c76007f44047b5c275e4d9228bc`
- Phase branch: `chore/phase-8a-baseline-safety`
- Planned commit: `chore: establish phase 8a cloud migration baseline`
- Final commit SHA: reported after commit, not self-recorded here

## Worktree decision

The user approved restoration of these pre-existing tracked documentation deletions:

- `PHASE_7_DEFENSE_VALIDATION.md`
- `QUICK_UI_FIX_COMPLETE.md`
- `UI_REFACTOR_PHASE1_COMPLETE.md`

All three were restored exactly from `main`. The untracked
`CIVICLEAR_IMPLEMENTATION_ROADMAP.md` is an intended Phase 8A artifact.

## Verified external backup

- Root:
  `C:\Users\63923\Desktop\database\backups\DILG-RC\phase-8a\20260728-175019`
- Files: 23
- Total bytes: 67,738,848
- Manifest: `SHA256SUMS.csv` in the backup root
- The backup is outside the repository and is not staged in Git.
- No live `.env` was copied.

### SQLite snapshot

- Source: `database/database.sqlite`
- Source journal mode: `delete`
- Source WAL/SHM: absent
- Source integrity: `ok`
- Source SHA-256:
  `F2DD1E2EE80A955095D6B31D35037F6297FD88DC5958C6537C8F961D6988EF30`
- Online-backup integrity: `ok`
- Backup SHA-256:
  `CADE74BC0EBCA88BFF5F790B4CF3C48C9DF044E18BBA126A0856E92E005B5628`

The online backup is logically equivalent but not byte-identical. All application table
counts match. The source schema-version counter is 81 and the new backup counter is 1;
table schema and data counts match.

## Database counts

| Table | Source | Backup |
| --- | ---: | ---: |
| cache | 6 | 6 |
| cache_locks | 0 | 0 |
| complaints | 0 | 0 |
| failed_jobs | 0 | 0 |
| job_batches | 0 | 0 |
| jobs | 0 | 0 |
| migrations | 15 | 15 |
| password_reset_tokens | 0 | 0 |
| records | 0 | 0 |
| report_timelines | 9 | 9 |
| sessions | 1 | 1 |
| users | 27 | 27 |
| violation_reports | 11 | 11 |

Existing reports include seeded/demo history, but the current schema has no explicit
`is_test_data` flag. Phase 8B must identify known seed provenance explicitly rather than
guessing from report content.

## Toolchain baseline

| Component | Result |
| --- | --- |
| Windows | Microsoft Windows NT 10.0.26200.0 |
| Git | 2.49.0.windows.1 |
| PHP | 8.2.12 |
| PDO drivers | mysql, sqlite |
| Composer | 2.10.1 |
| Node.js | v22.21.0 |
| npm | 11.14.1 |
| Java | OpenJDK 11.0.16.1 |
| Python | 3.12.5 |
| pip | 26.1.1 |
| ADB | Not available on PATH |
| FastAPI virtual environment | Existing interpreter stalled during version inspection |

No service was listening on ports 8000, 9000, or 8081 during initial inspection.

Java 11 is below the Java 17 target previously documented for Android native builds.

## Test-database safety

Laravel database-test isolation was proven before invoking the suite:

- `phpunit.xml` forces `DB_CONNECTION=sqlite`.
- `phpunit.xml` forces `DB_DATABASE=:memory:`.
- `DB_URL` is blank in the PHPUnit environment.
- `.env.testing` is absent.
- `tests/TestCase.php` does not override the database.
- Feature tests using `RefreshDatabase` therefore operate on the in-memory database,
  not `database/database.sqlite`.

## Baseline verification

| Area | Command | Result |
| --- | --- | --- |
| Laravel | `php artisan test --no-ansi` | Timed out after 60 seconds with no test output; Phase 8A-started processes were stopped |
| FastAPI | `python -m pytest -q` | Blocked: system Python has no `pytest` |
| FastAPI venv | Version/import inspection | Existing venv interpreter stalled; stopped as a Phase 8A process |
| Mobile types | `npm run typecheck` | Passed |
| Mobile lint | `npm run lint` | Passed |
| Mobile tests | `npm test` | 3 suites, 11 tests passed |
| Expo Doctor | `npm run doctor` | Unavailable: local `expo-doctor` command not found |
| Laravel frontend | `npm run build` | Passed; Vite transformed 55 modules |

Before and after checks showed no tracked test/build output changes. `public/build` is
ignored and was the only production-build output location.

Phase 8A intentionally did not install missing tools, change dependencies, or fix the
Laravel/FastAPI baseline stalls.

## Artifact manifest

| Artifact | Bytes | SHA-256 |
| --- | ---: | --- |
| `mobile/assets/models/best_float16.tflite` | 22,426,678 | `DEB4E346701A063CFA39494FD9AB86882269CA827795304DB27E60F8E42A7C0F` |
| `mobile/assets/models/best_float32.tflite` | 44,767,514 | `6A796A9B6E14ABE1C13A36AE5FA74F13504BC0C335ADCCACAEA0E58AA3FBD808` |
| `mobile/assets/models/labels.txt` | 92 | `63C27FD6842EFB23A300D5427D066314021C59D2C02FDE3AAB1C938C0E03CC16` |
| `mobile/assets/models/model_metadata.json` | 3,422 | `30B2AC8A60C281615CC48CF701BAD05543772CC18EF42DED38354C6C7E7869CC` |
| `ai-inference-server/models/nlp/civiclear_nlp_model.joblib` | 82,060 | `EEF576AA7B257B60674548C1E0322A9F8872CB543869314BAE54BB445D238F6B` |
| `ai-inference-server/models/nlp/nlp_metrics.json` | 1,510 | `D1F6BAE90B00AF9D8F85F19CEB1D6BB2B6150D6D3298D8D04C5DC053FF6D37C3` |
| `public/gis/boundary.geojson` | 22,347 | `5FC4AE4B8D6143C836BCD068371CBDC6A0635A0E19E8F13C5B585B8CBB740B94` |
| `public/gis/barangay_halls.geojson` | 17,343 | `7DE2B072A5E476F56DA3DB255990EAA2B8577269CE29062130E5A90476E1D2E8` |
| `public/gis/santa_cruz_barangays.geojson` | Missing | Missing |

## GIS baseline

- `boundary.geojson`: valid FeatureCollection, one MultiPolygon.
- Boundary name: Santa Cruz (Capital), Laguna.
- Dataset date property: 2022-11-09.
- Valid-on property: 2023-11-06.
- Status: validated municipal boundary; authoritative government provenance has not
  been formally recorded.
- `barangay_halls.geojson`: valid FeatureCollection, 26 Point features.
- Barangay hall points are office references, not jurisdiction boundaries.
- Municipality coverage validation is available.
- Exact barangay polygon detection is unavailable.
- DILG manual routing remains required.

## Upload baseline

`storage/app/public` contained one 14-byte tracked placeholder file and no report-photo
payloads at backup time. The directory state was copied and included in the external
manifest.

## Runtime-change confirmation

Phase 8A:

- did not change database schema or data;
- did not change Laravel, FastAPI, or mobile runtime code;
- did not install or upgrade dependencies;
- did not create Supabase or Google Cloud resources;
- did not move model execution;
- did not push or merge a branch.

## Phase 8B readiness

The recovery baseline, hashes, architecture, and data counts are available. Phase 8B is
safe to plan after this documentation diff is reviewed and committed.

The Laravel test timeout and unavailable FastAPI/Expo Doctor tooling are baseline
warnings. They must remain visible and should be diagnosed in an appropriately scoped
follow-up rather than hidden.

## Pre-commit worktree state

Immediately before staging:

```text
 M PROJECT_CONTEXT.md
?? CIVICLEAR_ARCHITECTURE.md
?? CIVICLEAR_IMPLEMENTATION_ROADMAP.md
?? PHASE_8A_BASELINE.md
```

No runtime-code, schema, database, model, upload, dependency, lockfile, secret, or
generated-build file was modified or staged. The resulting commit SHA must be reported
after commit and must not be inserted into this same commit.
