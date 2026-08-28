# DILG-RC Project Context

## Phase 8F Stage B mobile native cleanup

Stage B is implemented for verification on
`chore/phase-8f-stage-b-remove-mobile-tflite`, starting from main commit
`ef85c62b4f2dbd59553ab1d50adab23bdf30d77a`.

The Android client no longer declares or bundles `react-native-fast-tflite`,
`react-native-nitro-modules`, a TFLite Expo plugin, mobile model artifacts, or the
phone-side model loader/decoder/NMS path. General photo preparation and the complete
Stage A server-AI submission, credential, recovery, history, and polling contracts are
preserved. Existing drafts drop obsolete phone-inference keys during normalization.

The FastAPI-owned Float16 model remains unchanged at SHA-256
`deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f`.
The known physical-device `Discard Local Recovery` defect remains isolated in the
deferred Phase 8F-R follow-up and is not changed by Stage B.

## Phase 8F Stage A mobile server-AI implementation

Phase 8F-0 was manually verified and merged at
`48f46c4b6d8d46812ee17e080e465fc1e33e4dbd`. Stage A is implemented for verification
on `feature/phase-8f-mobile-server-ai-flow`.

The mobile submission screen now sends an app-owned photograph snapshot, description,
GPS, accuracy, timestamp, and stable per-draft Idempotency-Key to Laravel. It does not
initialize local TFLite or send citizen/mobile AI classification fields. TFLite packages,
plugins, tests, and artifacts remain installed until a separately approved Stage B
parity/removal gate.

Recovery states distinguish prepared, submitting, uncertain, submitted, retryable
failure, and permanent failure. An interrupted request becomes uncertain without an
automatic retry; explicit retry reuses the immutable snapshot and key. New opaque
Tracking Tokens are case-sensitive and stored only in SecureStore. AsyncStorage keeps
safe metadata, navigation uses local record IDs, and legacy sequential-only history is
not reinterpreted as an anonymous credential.

Report, server-AI, verification, and barangay-routing states are independently displayed
and polled with bounded backoff. Android backup is disabled. Production API configuration
requires public HTTPS. The token-bearing status URL remains a production access-log
redaction blocker documented for deployment.

## Phase 8F-0 mobile contract readiness

Phase 8F-0 is implemented for verification on
`fix/phase-8f-mobile-unclassified-contract`, starting from main commit
`e71829c3d46e3c80a0a2b66d476c2ec64f1e4090`.

It permits a new mobile submission to omit `selected_violation_type`. Laravel stores a
centralized, server-owned `Unclassified` sentinel to satisfy the existing non-null
string column, but mobile and public resources expose a null citizen selection instead
of the literal. Clients cannot submit the sentinel explicitly, existing genuine
categories remain accepted, and staff interfaces display `Awaiting Staff
Classification`.

Citizen-category analytics exclude the internal state. AI recommendations remain
advisory and independent, while official classification and verifier fields remain
null until authorized staff verification. The omitted category is normalized before
idempotency fingerprinting, so an exact replay is stable and a later attempt to change
the citizen category conflicts.

This prerequisite does not modify schema or mobile runtime. Phase 8F Stage A must not
begin until this branch is tested and merged by the user.

## Phase 8B report security implementation

Phase 8B is implemented on
`feature/phase-8b-report-schema-tracking-security`, created from Phase 8A commit
`8b69424336addbe913b37409dbbd139ecc171ba1`.

Laravel now creates a concurrency-safe human-readable Report Number and an opaque
public Tracking Token. A random non-secret nonce allows authorized idempotent replay
to reproduce the same token through a server-only derivation HMAC. The database stores
the nonce, a separate purpose-keyed token lookup hash, and a separate purpose-keyed
idempotency hash; it never stores the raw Tracking Token or idempotency key. Public
status lookup rejects Report Numbers, legacy report IDs, and internal numeric IDs.

The migration adds independent report, AI, upload, task, barangay-assignment,
verification, operational-error, official-classification, duplicate, and test-data
fields without dropping the legacy schema. Original verification values are retained
in `legacy_verification_status`, while baseline rows without reliable verifier
provenance become `Pending` with nullable official fields. The ten Phase 8A rows with
explicit `RoadClearingViolationSeeder` provenance are marked `is_test_data = true`;
new citizen submissions default to false.

A temporary copy of the Phase 8A SQLite backup passed migrate, rollback, and reapply.
All 27 users, 11 reports, and 9 timelines were preserved, integrity remained `ok`,
all 11 Report Numbers were unique, and there were no orphan timelines. Rollback
restored the original verification distribution of 3 `Unverified` and 8
`Valid Violation` records. Six concurrent allocator processes received six unique
Report Numbers. Two Laravel server processes submitting the same idempotency key
returned one creation and one replay with the same Report Number and Tracking Token;
the database contained exactly one new report and one timeline.

The Laravel regression suite passes 31 tests with 217 assertions. Phase 8B does not
migrate to Supabase, deploy cloud resources, move image inference to FastAPI, refactor
photo storage, implement Cloud Tasks, or remove mobile TFLite. Older mobile-history
entries containing only sequential tracking IDs cannot use anonymous token tracking
until an explicit migration or the Phase 8F mobile update.

## Phase 8A architecture freeze

Phase 8A is active on `chore/phase-8a-baseline-safety`. The approved future architecture
is recorded in `CIVICLEAR_ARCHITECTURE.md`, and the gated implementation sequence is
recorded in `CIVICLEAR_IMPLEMENTATION_ROADMAP.md`.

The starting commit is `f2f2765a8a025c76007f44047b5c275e4d9228bc`. A verified external
backup was created at
`C:\Users\63923\Desktop\database\backups\DILG-RC\phase-8a\20260728-175019`.
The SQLite source and online backup both pass integrity checks and contain 27 users,
11 violation reports, 9 report timelines, and 15 migrations.

Phase 8A changes documentation only. No schema, data, runtime, dependency, or cloud
resource change is authorized in this phase. See `docs/phase-records/PHASE_8A_BASELINE.md` for hashes,
toolchain versions, test results, and current blockers.

## Current phase

Phase 7 defense stabilization is active on
`feature/phase-7-defense-stabilization`. Baseline verification reproduced and fixed two
actual blockers: public tracking selected the oldest timeline entry for `latest_action`,
and Windows EAS archiving traversed an inaccessible FastAPI pytest cache. A complete
Laravel defense-chain test now covers mobile submission metadata, AI persistence, DILG
visibility/details, manual barangay routing, barangay visibility, status update, and
public mobile tracking. Local EAS archive inspection retains the TFLite model and labels
while excluding unrelated backend and generated files.

Desktop validation is green, but physical TFLite, camera/gallery, GPS, LAN submission,
and mobile refresh evidence must still be recorded on the Infinix Hot 40i before physical
end-to-end acceptance is marked complete. See `docs/phase-records/PHASE_7_DEFENSE_VALIDATION.md`.

Phase 6A-6D source implementation is complete on
`feature/phase-6-fastapi-ai-integration`. The repository now contains one local
FastAPI server for the real CivicClear scikit-learn NLP pipeline, municipal GIS
validation, and multimodal fusion. Laravel saves reports before calling FastAPI,
persists safe AI summary fields, retains reports when AI is offline, and allows only
DILG admins to retry pending/failed processing. Staff report details and public mobile
tracking display advisory summaries without exposing raw inference responses.

The supplied NLP artifact is a joblib `Pipeline` with `TfidfVectorizer` and
`LogisticRegression`, serialized with scikit-learn 1.9.0. Its six trained classes
include `no_violation`; that class is preserved as an explicit non-violation signal and
is never fabricated into one of the five violation categories. The artifact SHA-256 is
`eef576aa7b257b60674548c1e0322a9f8872cb543869314bae54bb445d238f6b`.

Phase 6 automated verification: 5 FastAPI tests pass with the real model; 21 Laravel
tests pass with 110 assertions; 11 mobile tests, TypeScript, ESLint, and the Vite
production build pass. A live local Uvicorn multimodal request also passed. Full
physical Android end-to-end acceptance remains blocked by the unresolved Phase 5C
custom-build model-loading/device test.

Phase 5D + Phase 5E source implementation is now present on top of
`feature/phase-5c-mobile-tflite-inference`:
the mobile citizen workflow captures foreground GPS through Expo Location, validates
Santa Cruz municipal coverage through `POST /api/gis/detect-barangay`, submits
multipart reports to `POST /api/mobile/reports`, stores the returned Tracking ID in
local history, and refreshes public status/timeline data through
`GET /api/mobile/reports/status/{tracking_id}`. The citizen still does not choose an
official violation category; the mobile app maps the Phase 5C image result to the
existing Laravel `selected_violation_type` compatibility field and marks unclear cases
for manual review.

Phase 5C implementation is present:
the Expo app loads the Float16 YOLOv8s model through `react-native-fast-tflite`,
preprocesses selected photos to Float32 NHWC 640x640 RGB with letterboxing, decodes
raw `[1, 9, 8400]` output, applies confidence filtering and per-class NMS, displays
the AI-assisted result, and persists model identity and detections in the local draft.

Phase 5C is not yet eligible to close because a custom Android build and representative
physical-device inference have not completed. Expo prebuild and native autolinking pass;
Metro Android/Hermes export passes with the Float16 model and labels bundled. The local
Gradle build requires Java 17 and its Gradle distribution did not finish downloading
within the verification window. Standard Expo Go cannot run this native module.

Latest automated verification (July 15, 2026): 11 mobile inference tests pass;
TypeScript and ESLint pass; Expo Android/Hermes export bundles the Float16 model with
the expected SHA-256 hash; and the existing Laravel suite remains green at 17 tests and
94 assertions. Expo Doctor passes 17 of 18 checks; its only warning is React Native
Directory metadata marking `react-native-fast-tflite` untested on the New Architecture.
Composer and the Laravel frontend npm tree report zero known advisories. The Expo SDK 54
mobile tree reports 19 moderate transitive advisories and no high or critical issues;
the available forced fix would make a breaking Expo 57 upgrade and was not applied.
Android prebuild explicitly removes `RECORD_AUDIO` and retains only the camera capability
needed by the evidence workflow.

Previously completed: Phase 5B Native Citizen Mobile App camera/gallery report form,
local draft persistence, validation, and mobile UI improvement.

Previously completed: Phase 4D correction and hardening with safe municipality
validation, temporary DILG barangay routing, role-filtered GIS data, API Resources,
and automated tests. Phase 5A created the native Expo mobile foundation.

## Violation classification ownership

The Phase 5B citizen mobile form collects photo evidence and optional incident context.
Citizens must not choose the official violation category.

Phase 5C adds an AI-suggested category and confidence score. The visible-condition
prediction must be mapped to the approved DILG taxonomy and verified or corrected by
authorized staff before it becomes the official classification. Low-confidence or
unclear evidence enters manual review; the system must not fabricate a category.

The existing dashboards still depend on the Laravel `selected_violation_type` field.
Phase 5D now fills that compatibility field from the AI class mapping rather than a
citizen choice. Low-confidence and no-detection cases use the existing
`Other Road Clearing Violation` bucket and set `needs_manual_review=true`; authorized
staff must still verify or correct the final classification.

## Authoritative GIS data state

- `public/gis/boundary.geojson` contains one Santa Cruz municipal MultiPolygon.
- It must only be used to decide whether GPS coordinates are inside Santa Cruz.
- Barangay polygons are not currently available.
- `public/gis/santa_cruz_barangays.geojson` is the reserved future barangay dataset.
- Twenty barangay office points are researcher-verified Google Maps entries. Gatid,
  Malinao, Poblacion I, San Pablo Sur, Santo Angel Norte, and Santo Angel Sur remain
  config-centroid fallbacks requiring validation.

## Location assignment contract

`BarangayAssignmentService::assignReportLocation()` performs two separate operations:

1. Validate Santa Cruz municipality coverage.
2. Attempt barangay detection only against `santa_cruz_barangays.geojson`.

If the point is outside Santa Cruz, the report is unassigned with status
`outside_coverage`. If the point is inside but barangay polygons are unavailable, the
report is unassigned with status `barangay_boundary_unavailable` and enters the DILG
review queue. The municipal name `Santa Cruz (Capital)` is never a barangay assignment.

## Temporary DILG routing

DILG Admins can route review-queue reports from `/needs-barangay-review`. Routing
requires a configured barangay, a reason, and explicit confirmation. The actor and
timestamp are stored and a report timeline entry is created. Barangay staff cannot use
this action.

The routing warning shown to users is:

> Barangay assignment is temporarily reviewed by DILG because barangay-level boundary
> data is not yet available.

## Effective barangay

The application uses this precedence rule:

```text
effective_barangay = detected_barangay ?: manually_assigned_barangay
```

Automatic polygon detection therefore overrides the temporary route. Barangay queries
use the `forEffectiveBarangay()` model scope, preventing unassigned or other-barangay
reports from appearing in staff dashboards and GIS APIs.

## Nearest office rule

`BarangayOfficeService` uses the Haversine formula. The result is labelled
"Recommended Barangay Office for Follow-up" and must never be used as proof of
jurisdiction. A result based on unvalidated coordinates is marked `provisional` with a
production-validation warning.

## API boundaries

Public, throttled endpoints:

- `POST /api/mobile/reports`
- `GET /api/mobile/reports/status/{tracking_id}`
- `GET /api/mobile/violation-types`
- `GET /api/mobile/barangays`
- `POST /api/gis/detect-barangay`

Authenticated, throttled endpoints:

- `GET /api/mobile/reports/{id}`
- `GET /api/gis/reports`
- `GET /api/gis/barangay-offices`
- `GET /api/gis/hotspots-summary`

GIS report and hotspot queries are municipality-wide for DILG Admins and constrained to
the authenticated staff member's effective barangay for barangay staff. Public status
responses do not expose contact numbers, reporter identity, internal remarks, or manual
routing evidence.

## Production requirements still open

- Obtain and validate official barangay polygon GeoJSON.
- Validate all barangay hall coordinates with the LGU.
- Replace sequential public tracking IDs with high-entropy tracking credentials.
- Add stronger anonymous-submission abuse controls beyond throttling.
- Set production environment values, HTTPS, backups, monitoring, and `APP_DEBUG=false`.
- Complete an Android custom development build with Java 17 and test actual Float16
  inference, latency, repeated runs, and memory use on the Infinix Hot 40i.
- Validate the decoder against representative images for all five trained classes and
  compare at least one TFLite result with the source YOLO checkpoint.
- Review the Ultralytics AGPL-3.0 checkpoint licensing terms before distribution.
- Resolve Expo SDK 54/tooling audit advisories through a controlled SDK upgrade; never
  apply a forced incompatible major-version audit fix.

Phase 5D + Phase 5E source implementation and automated checks are complete, but
physical Android acceptance remains pending. Phase 6A-6D proceeded by explicit user
authorization, but citizen authentication, push notifications, deployment, and release
work remain blocked until a custom Android build validates Phase 5C inference plus the
Phase 5D/5E submission and tracking flow on a representative phone.

## Phase 8C server-side inference

Phase 8C moves the preferred Float16 TFLite model into the existing FastAPI service
without removing the mobile implementation. The source mobile artifacts remain
unchanged, and their copies under `ai-inference-server/models/image` have matching
SHA-256 hashes.

The server uses `ai-edge-litert==2.1.6` on Python 3.12/Windows AMD64. It reproduces the
approved RGB Float32 NHWC letterbox contract, validates raw Float32 output
`[1, 9, 8400]`, converts its normalized xywh coordinates before reversing letterboxing,
and performs 0.25 candidate filtering plus per-class NMS. Candidates below 0.60 remain
low-confidence evidence requiring staff review. One interpreter is loaded at startup;
`set_tensor`, `invoke`, and copied `get_tensor` are serialized by a lock.

The versioned multipart endpoints are `POST /v1/predict/image` and
`POST /v1/predict/multimodal`. The latter integrates image, NLP, municipal GIS, and
fusion results. Its optional `barangay_hint` is ignored for jurisdiction and assignment.
AI review fields and barangay-routing review fields are kept separate.

`GET /health` is liveness/component status. `GET /ready` returns HTTP 503 when a
required inference component or the municipal boundary is unavailable. The known
absence of barangay polygons is reported truthfully but does not make inference
unready. Cloud deployment, Laravel orchestration, private photo persistence, and mobile
server-AI submission remain later phases.

## Phase 8D durable private photographs

Phase 8D commits the report, Tracking Token credentials, Report Number, submission
fingerprint, and initial timeline before photograph sanitation, private storage, or
transitional AI work. New photographs are decoded with GD, bounded by byte/dimension/
pixel limits, corrected for all EXIF orientations, and newly encoded as JPEG or PNG.
JPEG, PNG, and static WebP inputs are accepted; WebP becomes JPEG, PNG remains PNG,
and animated WebP/APNG or other unsupported formats are rejected.

Sanitized evidence uses a dedicated private Laravel disk and a random opaque
`photo_object_key`; legacy `image_path` remains separate and is not automatically
migrated or exposed by the new storage service. Anonymous submission and tracking
responses reveal only safe photo status/availability. Authorized DILG administrators
and correctly assigned barangay staff stream evidence through a report-scoped,
non-cacheable controller route.

Photo attempts use a five-minute recoverable lease, a server-generated token hash, and
a pending object key written to the database before storage. Live leases return HTTP
202, expired leases can be reclaimed, partial writes remain traceable, and targeted
compensation never performs broad deletion. Same-key retries preserve one report,
timeline, Report Number, and Tracking Token; conflicting non-photo payloads or
prohibited photo replacement return HTTP 409. Phase 8E now provides stored-photo
FastAPI orchestration. The API replay contract is ready, but the current mobile client
does not send an Idempotency-Key yet; Phase 8F must retain a stable high-entropy key per
draft for response-loss recovery.

## Phase 8E Laravel AI orchestration

Phase 8E replaces the transitional JSON-only call with a reusable `ProcessReportAi`
action. After Phase 8D makes the report and sanitized private photograph durable, an
explicit synchronous local dispatcher verifies the stored size and SHA-256, opens a
fresh stream, and sends the actual JPEG or PNG to FastAPI
`POST /v1/predict/multimodal`.

The complete Phase 8C image, text, GIS, fusion, model, timing, and review contract is
validated before any successful AI result is persisted. FastAPI GIS remains advisory
and cannot overwrite Laravel routing. Mobile-supplied AI fields are accepted only for
temporary request compatibility and are ignored as evidence. AI populates
`ai_possible_violation` but never official staff-verification fields.

AI processing uses `pending`, `processing`, `completed`, and `failed` states with an
attempt counter and recoverable hashed-token lease. A live lease cannot be claimed
twice, stale workers cannot overwrite a newer attempt, and only authenticated DILG
retry can reclaim failed or expired work. Anonymous replay cannot retry AI; it may
start the first attempt only when it has just recovered the report's first durable
photograph and no AI claim ever occurred.

FastAPI outages, invalid schemas, timeouts, integrity failures, and operational errors
preserve the report, Tracking Token credentials, timeline, and private photograph.
Phase 8E uses synchronous local dispatch only. Cloud Tasks replacement remains Phase
10A, while mobile server-AI submission, opaque-token handling, idempotency, and polling
remain Phase 8F.

## Phase 9A PostgreSQL migration — activated

Phase 9A completed its guarded Supabase PostgreSQL migration on 2026-08-08. All 19
migrations passed in a protected disposable schema before the immutable final SQLite
backup was imported into the private `civiclear` schema. The final import preserved 91
rows across 13 tables; all 13 canonical digests matched, with 50 indexes and 3 foreign
keys verified.

Laravel now uses the Supabase Session Pooler with TLS `verify-full`, schema
`civiclear`, and PostgreSQL writes enabled. Gate 3A created one controlled,
idempotent, `is_test_data = true` report (`RCV-2026-0028`). Gate 3B then restored
database-backed sessions, cache, and queues, verified a writable TLS session, and
lifted maintenance mode. Final HTTP health, login, redirect, and safe invalid-tracking
checks passed, and the temporary server was stopped.

The immutable SQLite backup and both configuration rollback copies remain outside
Git. Because PostgreSQL has accepted a test write and may now accept normal writes,
SQLite must not be restored blindly; any rollback requires a new write freeze and
explicit PostgreSQL-delta reconciliation. Phase 9B storage, Supabase Auth/Data API
use, mobile SDK changes, and FastAPI database access remain outside Phase 9A.

An isolated Phase 9A recovery drill subsequently restored the immutable SQLite backup
to a new external copy, applied the current migration set, and passed integrity,
foreign-key, relationship, credential-hash, opaque-tracking, authenticated-view, and
HTTP checks. Supabase and the live `.env` were unchanged. The reconciliation check
identified only controlled test report `RCV-2026-0028` and its timeline as newer than
the frozen snapshot. The roadmap recovery exit gate is therefore tested, while a real
rollback remains separately approval-gated and must reconcile all PostgreSQL deltas.
