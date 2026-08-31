# Phase 8B Report Schema and Tracking Security

## Repository baseline

- Starting branch: `main`
- Complete starting commit: `8b69424336addbe913b37409dbbd139ecc171ba1`
- Phase branch: `feature/phase-8b-report-schema-tracking-security`
- Planned commit: `feat: secure report schema and public tracking`
- Phase 8A backup:
  `C:\Users\63923\Desktop\database\backups\DILG-RC\phase-8a\20260728-175019`
- Phase 8A backup modified: no
- Real `database/database.sqlite` migrated or tested: no

The repository did not contain a separate `PHASE_8A_RECOVERY.md`. The committed
`PHASE_8A_BASELINE.md` records the verified recovery baseline, hashes, counts, and
Phase 8B readiness. The external backup was present before work began.

## Migration

Migration:

```text
2026_07_28_000001_add_phase_8b_report_security_fields.php
```

Supporting table:

```text
report_number_sequences
year primary key
last_number
timestamps
```

Added report fields:

```text
report_number
token_derivation_nonce
tracking_token_hash
idempotency_key_hash
report_status
legacy_verification_status
photo_upload_status
task_creation_status
barangay_assignment_status
ai_manual_review_reason
processing_error_code
processing_error_message
ai_possible_violation
ai_possible_violation_confidence
official_violation_type
verified_by
verified_at
is_duplicate
is_test_data
processed_at
```

`report_number`, `token_derivation_nonce`, `tracking_token_hash`, and
`idempotency_key_hash` have database uniqueness constraints. `verified_by` is nullable,
references `users`, and becomes null when the referenced user is deleted. State,
operational-error, duplicate, and test-data query fields are indexed.

The migration is additive. It retains `report_id`, `status`, existing AI fields,
`barangay_detection_status`, `ai_processed_at`, numeric report primary keys, and all
timeline foreign keys.

## Report Number strategy and backfill

New numbers retain the `RCV-YYYY-NNNN` format. `ReportNumberService` inserts the year
sequence if missing, atomically increments it inside a database transaction, and reads
the allocated value before committing.

During backfill:

- a valid and unique legacy `report_id` is copied to `report_number`;
- malformed, missing, or conflicting values receive the next sequence number;
- the original `report_id` is never overwritten;
- the sequence starts after the largest valid suffix for each year;
- new reports always receive a non-null Report Number.

The Phase 8A baseline contained 11 valid, unique legacy values, so all 11 were copied
without replacement.

## Tracking Token design

The implementation follows the approved purpose-separated construction:

```text
raw_tracking_token =
Base64URL(
    HMAC-SHA256(
        REPORT_TRACKING_TOKEN_DERIVATION_KEY,
        "civiclear:tracking-token:v1:" + token_derivation_nonce
    )
)

tracking_token_hash =
Hex(
    HMAC-SHA256(
        REPORT_TRACKING_TOKEN_LOOKUP_KEY,
        "civiclear:tracking-lookup:v1:" + raw_tracking_token
    )
)
```

`token_derivation_nonce` is 32 random bytes stored as 64 lowercase hexadecimal
characters. It is non-secret and cannot produce the token without the derivation key.
The lookup and derivation keys and HMAC domains are different.

Idempotency keys use:

```text
REPORT_IDEMPOTENCY_HMAC_KEY
"civiclear:idempotency:v1:"
```

Only the nonce and keyed lookup hashes are persisted. Raw Tracking Tokens and raw
idempotency keys are not stored or logged. Missing or shorter-than-32-byte security
configuration fails with a controlled public service-unavailable response.

`.env.example` contains names only. PHPUnit injects test-only values.

## Idempotent replay

`POST /api/mobile/reports` accepts the preferred `Idempotency-Key` header. A supplied
key is validated as a 16-255-character opaque value and stored only as a purpose-keyed
hash. The existing mobile client remains temporarily compatible when the header is
absent, but guaranteed retry deduplication requires the client to reuse the same key.

The first keyed request returns HTTP 201. A later or concurrent request with the same
key returns HTTP 200, the existing Report Number, and the same reproducible Tracking
Token. Replay creates no additional report, initial timeline, stored image, or AI
request. Database uniqueness resolves races in favor of the already-created report.
Phase 8B does not rotate tokens.

## Public tracking and mobile compatibility

Public status is:

```text
GET /api/mobile/reports/status
Authorization: Bearer <tracking_token>
```

Laravel reads the opaque token only from the standard Authorization header, hashes it,
and looks up `tracking_token_hash`. It rejects a missing credential, query-string or
route token, Report Number, legacy `report_id`, internal numeric ID, malformed token, or
unknown token with a generic not-found response.

The creation response temporarily puts the opaque token in `tracking_id`, which is the
field expected by the existing mobile application. It separately returns
`report_number`. This does not change the database meaning of legacy `report_id`.

Existing reports have no recoverable raw token and remain accessible to authorized
staff. Weak tokens were not generated from sequential identifiers. Old mobile-history
entries containing only sequential identifiers may no longer support anonymous
tracking until an explicit migration or Phase 8F updates the mobile flow.

Public responses exclude credential hashes, the nonce, internal numeric IDs, raw
idempotency keys, contact information, staff notes, raw AI responses, and stack traces.

## State and classification mapping

Approved verification states:

```text
Pending
Valid Violation
Invalid Report
Duplicate
Outside Jurisdiction
Insufficient Evidence
```

Baseline records lacked reliable `verified_by` provenance. Their original state was
copied to `legacy_verification_status`, and the authoritative fields were set to:

```text
verification_status = Pending
official_violation_type = null
verified_by = null
verified_at = null
```

Rollback restores the original `verification_status` before removing the compatibility
column.

Other mappings:

- `report_status` receives legacy `status`;
- `ai_possible_violation` receives `final_ai_prediction`, falling back to
  `predicted_violation_category`;
- the corresponding AI confidence is preserved;
- `processed_at` receives `ai_processed_at`;
- an image path maps to `photo_upload_status = uploaded`, otherwise `not_provided`;
- task creation remains `not_started`;
- manual assignment maps to `manually_assigned`;
- a detected barangay maps to `auto_detected`;
- missing polygons remain `barangay_boundary_unavailable`;
- other unresolved routing maps to `manual_assignment_required`.

AI processing writes only advisory fields. It never writes official classification or
staff-verification fields.

## Test-data provenance

Phase 8A records 11 baseline reports. Internal rows 1-10 and their exact Report Numbers
correspond to the ordered `RoadClearingViolationSeeder` manifest; those ten rows are
the explicitly verified demo set and are marked `is_test_data = true`. Row 11 is not
guessed and remains false.

The seeder now explicitly writes `is_test_data = true`. Citizen API submissions
explicitly write false. No name, description, or violation wording is used to infer
test data.

## Temporary-copy migration evidence

Source backup before migration:

| Item | Result |
| --- | ---: |
| users | 27 |
| violation_reports | 11 |
| report_timelines | 9 |
| migrations | 15 |
| integrity | `ok` |

After migration:

| Item | Result |
| --- | ---: |
| users | 27 |
| violation_reports | 11 |
| report_timelines | 9 |
| migrations | 16 |
| unique Report Numbers | 11 |
| null Report Numbers | 0 |
| explicitly marked test reports | 10 |
| Pending verification | 11 |
| orphan timelines | 0 |
| integrity | `ok` |

After rollback:

| Item | Result |
| --- | ---: |
| users | 27 |
| violation_reports | 11 |
| report_timelines | 9 |
| migrations | 15 |
| restored Unverified | 3 |
| restored Valid Violation | 8 |
| integrity | `ok` |

Reapplication produced the same migrated counts, mappings, and integrity result.

## Concurrency and replay evidence

Six independent PHP processes allocated Report Numbers against the same temporary
SQLite database:

```text
RCV-2043-0001
RCV-2043-0002
RCV-2043-0003
RCV-2043-0004
RCV-2043-0005
RCV-2043-0006
```

Allocated: 6. Unique: 6.

Two independent Laravel server processes then submitted the same payload and
idempotency key concurrently. Results:

```text
HTTP 201: RCV-2026-0012, replay=false
HTTP 200: RCV-2026-0012, replay=true
same raw Tracking Token: yes
new reports: 1
new initial timelines: 1
integrity: ok
```

## Automated tests

Focused suites:

- Phase 8B: 9 tests, 72 assertions
- Phase 6 AI regression: 4 tests, 16 assertions
- Phase 7 defense regression: 1 test, 35 assertions
- GIS assignment regression: 2 tests, 9 assertions

Full Laravel suite:

```text
31 tests passed
217 assertions
duration: 47.39 seconds
```

Changed PHP files pass Laravel Pint. A repository-root Pint scan remains affected by
the pre-existing inaccessible `ai-inference-server/.pytest_cache`; formatting was run
only against the changed PHP files.

## Explicit exclusions and remaining work

Phase 8B did not:

- migrate SQLite to Supabase/PostgreSQL;
- create Supabase, Cloudflare, Google Cloud, or Cloud Run resources;
- move TFLite inference to FastAPI;
- remove mobile TFLite code;
- refactor photo storage;
- implement Cloud Tasks;
- implement the Phase 8F mobile refactor;
- invent barangay polygons;
- push, merge, or deploy.

Phase 8F must make the mobile application generate and retain one idempotency key per
logical draft, store the returned opaque Tracking Token and Report Number separately,
and handle legacy history deliberately.
