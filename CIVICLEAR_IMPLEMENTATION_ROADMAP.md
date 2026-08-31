# CIVICLEAR Phase-by-Phase Implementation Roadmap

## Document purpose

This is the implementation playbook for CIVICLEAR's approved cloud-ready,
server-side-AI architecture. Complete the phases in order. Do not begin the next phase
until the current phase has:

1. met every required completion gate;
2. passed its relevant automated tests;
3. recorded its evidence and remaining limitations;
4. been reviewed and merged into the latest verified `main`.

This document describes target work. A checked box must represent observed evidence,
not an assumption.

## Current repository baseline

The following work is already part of `main`:

| Existing work | Commit |
| --- | --- |
| Phase 5D–5E mobile submission and tracking | `097adbe` |
| Phase 6 FastAPI AI integration | `21a456d` |
| Phase 7 stabilization and later fixes | `f2f2765` and earlier Phase 7 commits |

Do not recreate or re-merge Phases 5D–7.

Before creating Phase 8A, review these unexplained worktree deletions:

```text
docs/phase-records/PHASE_7_DEFENSE_VALIDATION.md
QUICK_UI_FIX_COMPLETE.md
UI_REFACTOR_PHASE1_COMPLETE.md
```

Determine whether each deletion is intentional, accidental, or an unrelated local
change. Preserve it until ownership and intent are known.

## Approved target flow

```text
Citizen mobile application
        │
        │ HTTPS multipart submission
        ▼
Public Laravel API on Azure Container Apps
        ├── Supabase PostgreSQL
        ├── Private Supabase Storage
        └── Azure Queue Storage
                    │
                    ▼
        Event-driven Container Apps Job
                    │
                    ▼
        Protected Laravel processing endpoint
                    │
                    ▼
        Internal FastAPI service on Azure Container Apps
                    ├── Image inference
                    ├── NLP classification
                    ├── Municipality GIS validation
                    └── Multimodal fusion
```

The mobile application communicates only with Laravel. Laravel remains the workflow,
validation, authorization, and public API authority. FastAPI returns advisory AI
results. The AI-detected category is shown automatically; assigned staff verify or
reject it, and correct it only when the evidence shows the AI category is wrong. Staff
verification—not AI alone—establishes the official classification.

## Non-negotiable safety rules

- Never use `git reset --hard`, `migrate:fresh`, `db:wipe`, or a destructive seeder.
- Never delete or overwrite an existing database or upload directory without a verified
  backup and explicit intent.
- Never discard unrelated worktree changes.
- Never place database credentials, Supabase service-role keys, cloud credentials,
  managed-identity token material, or other secrets in the mobile application or Git.
- Never treat AI output as the official classification.
- Never use barangay hall points as jurisdiction boundaries.
- Never claim physical-device, cloud, or positive-detection acceptance without observing
  it.
- Keep all source, data, labels, GeoJSON, and documentation in UTF-8.

## Branch workflow

For every phase:

1. Confirm the previous phase is merged and `main` is verified.
2. Review `git status` before switching branches.
3. Fetch or pull only when a configured upstream exists and local work is safe.
4. Create the exact phase branch from the verified `main`.
5. Keep the branch limited to the named responsibility.
6. Run the phase tests and relevant regression tests.
7. Review the complete diff.
8. Update `PROJECT_CONTEXT.md` with evidence and remaining blockers.
9. Commit intentionally and merge only after review.

## Authoritative state separation

Do not combine these domains.

### Report workflow

```text
Submitted
For Verification
Verified
Assigned
In Progress
Action Taken
Resolved
Rejected
Closed
```

### AI processing

```text
pending
processing
completed
failed
```

### AI evidence review

```text
ai_needs_manual_review
ai_manual_review_reason
```

Valid reasons include low image/text confidence, image-text disagreement, no image
detection, unsupported category, unclear evidence, insufficient text, and insufficient
fusion confidence.

### Barangay routing review

```text
needs_manual_barangay_review
barangay_assignment_status
```

Valid states include `auto_detected`, `barangay_boundary_unavailable`,
`manual_assignment_required`, `manually_assigned`, and `outside_coverage`.

### Operational failures

```text
photo_upload_status
task_creation_status
ai_processing_status
processing_error_code
processing_error_message
```

Upload, storage, task, network, model-readiness, and database failures are operational
errors. They are not AI evidence-review reasons.

---

# Phase 8 — Cloud-Ready Server-Side AI Pipeline

## Phase 8A — Baseline, Backup, and Architecture Freeze

**Branch:** `chore/phase-8a-baseline-safety`

**Objective:** Establish a recoverable, measured baseline without changing application
behavior.

### Prerequisites

- The three unexplained deletions have been reviewed.
- Existing user work is either committed, intentionally restored, or preserved
  separately.
- The starting commit and branch are recorded.

### Worktree ownership decision

Classify every pre-existing deletion or modification as:

- Phase 8A-owned;
- confirmed user-owned and intentionally retained;
- accidental and approved for restoration; or
- unresolved.

Do not include unrelated user-owned changes in the Phase 8A commit.

If the user confirms that a deleted file should remain deleted, ask whether the
deletion should:

1. be committed separately before Phase 8A;
2. remain uncommitted and block a clean Phase 8A completion; or
3. be included in Phase 8A because it is directly superseded by an approved Phase 8A
   document.

Do not make commit ownership decisions automatically. Phase 8A must not be reported as
clean while unexplained or unrelated changes remain mixed into its commit.

### Implementation tasks

- [ ] Record `git status`, current branch, HEAD, PHP, Composer, Node, npm, Python, Java,
      Android SDK, and ADB versions.
- [ ] Create a dated backup of `database/database.sqlite` outside the tracked source
      tree.
- [ ] Back up existing uploaded report photographs outside the tracked source tree.
- [ ] Back up environment templates without copying live secrets into documentation or
      Git.
- [ ] Record counts for users, violation reports, timelines, and migrations.
- [ ] Record the current Laravel, FastAPI, and mobile test results.
- [ ] Record image-model, NLP-model, label, and metadata SHA-256 hashes.
- [ ] Record municipal-boundary SHA-256 and available provenance fields.
- [ ] Save the approved architecture and this roadmap as UTF-8.
- [ ] Scan user-facing documentation for visibly corrupted text.
- [ ] Document the exact current local startup and data flow.

### Required evidence

- Backup paths, creation timestamps, sizes, and verification method.
- Database record counts.
- Exact test commands and pass/fail/timeout results.
- Artifact hashes and boundary provenance.
- List of unresolved limitations.

### SQLite backup safety gate

Do not rely only on a normal copy of `database/database.sqlite`.

Before backing up, inspect read-only:

- `PRAGMA journal_mode`;
- `PRAGMA integrity_check`;
- the presence of `database.sqlite-wal` and `database.sqlite-shm`;
- whether Laravel or another process may currently be writing to the database.

Use SQLite's online backup mechanism or an equivalent consistent read-only snapshot
when available. Do not run `VACUUM` against the source database.

After creating the backup:

1. Run `PRAGMA integrity_check` against the backup and require `ok`.
2. Compare the schema, migration count, and important table counts.
3. Record source and backup hashes.
4. Do not require binary hashes to match when the online backup legitimately produces
   an equivalent database with a different binary layout.
5. Require identical hashes only for a safe byte-for-byte copy made while no writer,
   WAL file, or changing database state exists.

Query `sqlite_master` before requesting table counts. Open the source read-only when
supported. Record `table not present` for an absent optional table instead of treating
it as a phase failure.

### Laravel test-database safety gate

Before running any Laravel test, inspect:

- `phpunit.xml`;
- `phpunit.xml.dist` when present;
- `.env.testing` when present, without printing secrets;
- `tests/TestCase.php`;
- database-related test configuration.

Prove that tests use one of:

- SQLite `:memory:`;
- a dedicated test-only SQLite file outside the live database path; or
- another clearly isolated test database.

Explicitly prove that tests cannot connect to `database/database.sqlite`.

If isolation cannot be proven, do not run database-affecting Laravel tests. Record the
suite as blocked by unsafe or unresolved test configuration. Do not create or edit test
environment configuration merely to force tests to run during Phase 8A.

### Generated-output safety

Before every build, export, doctor, lint, or test command:

1. Record `git status --short`.
2. Identify expected output directories.
3. Determine whether those directories contain tracked files.
4. Prefer a temporary output directory outside the repository when supported.
5. Do not overwrite tracked production artifacts merely to verify a build.

After each command:

1. Record `git status --short`.
2. Identify every new or modified file.
3. Remove only disposable files conclusively created by Phase 8A, using targeted,
   non-recursive operations.
4. If cleanup safety is uncertain, leave the file untouched, report it, and exclude it
   from the Phase 8A commit.

Never use `git restore`, `git checkout --`, `git clean`, or destructive deletion to
undo generated output.

### Command availability

Inspect `mobile/package.json` before invoking package scripts.

- Run `npm run typecheck`, `npm run lint`, `npm test`, and `npm run doctor` only when
  their scripts exist.
- Use non-interactive or CI mode when the existing framework supports it.
- Do not leave a test runner in watch mode.
- Do not use `npx` in a way that downloads a new package.
- Do not install Expo Doctor, testing tools, or dependencies merely for Phase 8A.
- Record missing scripts or unavailable tools as `not available`.

### Backup-directory and environment safety

On Windows:

- do not use `robocopy /MIR`, `robocopy /PURGE`, or any synchronization option that can
  delete destination content;
- create a new, uniquely dated backup directory and never reuse one;
- create per-file SHA-256 manifests for uploads, models, GIS files, and recovery
  documentation;
- record recursive file count and total bytes for directory backups.

Do not back up a live `.env` automatically. If a live environment backup appears
necessary, stop and request explicit permission. Prefer documenting required variable
names from `.env.example` without private values.

### Roadmap artifact

`CIVICLEAR_IMPLEMENTATION_ROADMAP.md` is an intended Phase 8A documentation file. After
confirming it contains no secrets and matches the approved roadmap, include it in the
Phase 8A commit even when no further textual correction is required.

### Commit evidence rule

`docs/phase-records/PHASE_8A_BASELINE.md` must record:

- the starting branch and complete starting commit SHA;
- the Phase 8A branch name;
- the planned commit message; and
- the pre-commit ending worktree state.

The final Phase 8A commit SHA is created from the committed file contents, so the file
cannot contain the SHA of the commit that contains it. Report the resulting final commit
SHA in the Codex final response after committing. Do not amend the commit merely to
insert its own SHA into `docs/phase-records/PHASE_8A_BASELINE.md`.

### Exit gate

- [ ] Worktree ownership is understood.
- [ ] SQLite and uploads have recoverable backups.
- [ ] Baseline tests and counts are recorded.
- [ ] No application behavior or schema changed.
- [ ] No existing data was deleted.

### Explicitly excluded

No schema migration, Supabase connection, model move, mobile refactor, Cloud Task, or
Cloud Run deployment.

---

## Phase 8B — Report Schema and Tracking Security

**Branch:** `feature/phase-8b-report-schema-tracking-security`

**Objective:** Add secure identifiers, idempotency, separate workflow fields, and
backward-compatible schema changes while SQLite remains the local database.

### Final implementation clarifications

Phase 8B uses a random non-secret `token_derivation_nonce`. Laravel derives the raw
Tracking Token with HMAC-SHA256 using
`REPORT_TRACKING_TOKEN_DERIVATION_KEY` and the domain
`civiclear:tracking-token:v1:`. It stores only the nonce and a second HMAC-SHA256
lookup hash produced with `REPORT_TRACKING_TOKEN_LOOKUP_KEY` and the distinct domain
`civiclear:tracking-lookup:v1:`. The raw token is never persisted.

New public tracking accepts only the opaque Tracking Token. It does not accept a Report
Number, legacy `report_id`, or internal numeric ID. Temporary response aliases may
place the opaque token in the field expected by the current mobile tracker without
changing the database meaning of `report_id`. Legacy mobile-history entries containing
only sequential identifiers may require migration in Phase 8F.

Approved verification states are `Pending`, `Valid Violation`, `Invalid Report`,
`Duplicate`, `Outside Jurisdiction`, and `Insufficient Evidence`. Historical rows
without reliable staff provenance are reset to `Pending`; their original legacy state
is retained separately. Official classification and verifier fields remain null.

`report_number` is nullable only during migration/backfill. A valid, unique legacy
`report_id` may be copied. Missing, malformed, or conflicting identifiers receive a
new number through the concurrency-safe sequence while the original legacy value is
preserved.

### Schema target

```text
report_number
tracking_token_hash
idempotency_key_hash

report_status
verification_status

ai_processing_status
photo_upload_status
task_creation_status

barangay_assignment_status
needs_manual_barangay_review

ai_needs_manual_review
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

### Implementation tasks

- [x] Add additive, reversible migrations that preserve existing reports and
      relationships.
- [x] Map existing status and AI fields deliberately; do not silently discard legacy
      values.
- [x] Generate a cryptographically random raw Tracking Token.
- [x] Store a deterministic keyed HMAC-SHA256 of the token for indexed lookup.
- [x] Hash idempotency keys with a separate purpose-specific keyed HMAC.
- [x] Store hashing keys only in server-side secret configuration.
- [x] Enforce database uniqueness for Report Number, token hash, and idempotency hash.
- [x] Make Report Number generation concurrency-safe using a sequence-like record,
      locking, or unique-conflict retry.
- [x] Add a migration/backfill strategy for existing sequential tracking identifiers.
- [x] Implement safe public tracking lookup using the raw token supplied by the client.
- [x] Implement one approved idempotent replay strategy.
- [x] Token rotation is not used by Phase 8B; deterministic replay returns the same token.
- [x] Separate AI recommendations from the nullable official classification.
- [x] Default real citizen submissions to `is_test_data = false`.
- [x] Explicitly mark known seeded/demo records as test data without description-based
      guessing.

### Required tests

- Concurrent Report Number generation.
- Unique token and idempotency constraints.
- Token hash lookup and invalid-token rejection.
- Idempotent retry creates exactly one report.
- Approved replay or rotation mechanism.
- Existing report, user, and timeline preservation.
- Public response privacy.
- AI and official classification separation.

### Exit gate

- [x] Existing records remain accessible.
- [x] Only token hashes are persisted as tracking credentials.
- [x] Duplicate requests do not create duplicate reports.
- [x] Public endpoints no longer rely solely on sequential Report Numbers.
- [x] Laravel regression tests pass.

### Explicitly excluded

No Supabase migration, server-side image inference, photo-storage refactor, or mobile
TFLite removal.

---

## Phase 8C — FastAPI Server-Side AI Inference

**Branch:** `feature/phase-8c-server-side-ai-inference`

**Objective:** Reproduce the existing mobile image-model contract on FastAPI without
removing the known mobile implementation until parity is verified.

### Implementation tasks

- [x] Copy the preferred TFLite model, labels, and metadata into a FastAPI-owned model
      directory.
- [x] Preserve the original artifacts and verify identical SHA-256 hashes.
- [x] Select a compatible official TFLite, LiteRT, or TensorFlow runtime based on the
      deployed Python/platform compatibility.
- [x] Load image and NLP models once during application startup.
- [x] Safely decode JPEG and PNG images and reject malformed or excessive inputs.
- [x] Correct orientation, convert to RGB, and reproduce the existing letterboxing and
      normalization contract.
- [x] Produce Float32 NHWC input `[1, 640, 640, 3]`.
- [x] Validate expected Float32 output `[1, 9, 8400]`.
- [x] Port YOLO decoding, confidence filtering, and per-class non-maximum suppression.
- [x] Preserve the exact trained class order.
- [x] Integrate existing NLP, municipality validation, and fusion logic.
- [x] Return structured image, text, GIS, fusion, version, hash, timing, and review
      sections.
- [x] Report image, NLP, municipal-boundary, and barangay-boundary readiness separately
      in `/health`.

### Final Phase 8C response and readiness clarifications

1. AI evidence review and barangay-routing review are separate. Structured inference
   responses use `review.ai_needs_manual_review` and
   `review.ai_manual_review_reasons`. The only AI review reasons are
   `no_image_detection`, `low_image_confidence`, `low_text_confidence`,
   `image_text_disagreement`, `unsupported_category`, `insufficient_text`, and
   `insufficient_fusion_confidence`. GIS routing uses
   `gis.needs_manual_barangay_review` and `gis.barangay_assignment_status`.
   `barangay_boundary_unavailable` is never an AI review reason. Operational failures
   use controlled error codes instead of AI-evidence uncertainty.
2. `GET /health` is the liveness and component-status endpoint and may return HTTP 200
   while reporting degraded components. `GET /ready` is the inference-readiness
   endpoint and returns HTTP 503 when the image runtime/model, NLP model, fusion
   service, or municipal boundary is unavailable. Missing barangay polygons alone do
   not make inference unready.
3. Optional multipart `barangay_hint` is non-authoritative compatibility context. It
   cannot override GIS results, establish jurisdiction, become an automatically
   detected barangay, bypass municipality validation, or be returned as confirmed
   without polygon evidence.
4. The retained TFLite interpreter serializes its complete mutable `set_tensor`,
   `invoke`, and copied `get_tensor` sequence with a lock. Image preprocessing occurs
   before the lock, while decoding and NMS occur after the copied output is released.

### Required tests

- [x] Model artifact contract and hash.
- [x] Preprocessing tensor shape, type, normalization, and letterbox mapping.
- [x] Decoder and per-class NMS fixtures.
- [x] Invalid and malformed image rejection.
- [x] Legitimate no-detection and low-confidence responses.
- [x] NLP and fusion regression tests.
- [x] Inside/outside municipality cases.
- [x] Missing barangay polygons return `barangay_boundary_unavailable`.
- [x] `/health` liveness and `/ready` required-component behavior.
- [x] AI-review/GIS-review separation and non-authoritative barangay hints.
- [x] Shared-interpreter invocation serialization.

### Exit gate

- [x] Real model loads on the target server runtime.
- [x] Automated preprocessing and decoding tests pass.
- [x] Representative-positive-image availability was audited; no approved positive
      evidence image exists in the repository, so the conditional test is not
      applicable yet.
- [x] No-detection is a controlled result, not an exception.
- [x] Existing FastAPI tests continue to pass.

### Explicitly excluded

Do not remove the mobile TFLite code yet. Do not deploy Cloud Run or connect Supabase.

---

## Phase 8D — Durable Report and Private Photo Pipeline

**Branch:** `feature/phase-8d-durable-report-photo-pipeline`

**Objective:** Save the report and a sanitized private photograph before AI processing.

### Implementation tasks

- [x] Introduce a private photograph-storage interface with a local private-disk
      implementation.
- [x] Create the report before attempting photo storage or AI.
- [x] Validate decoded content, actual MIME type, dimensions, and maximum size.
- [x] Correct orientation and remove unnecessary EXIF/thumbnail metadata.
- [x] Store the sanitized image under an opaque object key, not a public URL.
- [x] Keep quarantine disabled for the in-memory sanitation path and restrict the
      dedicated quarantine disk when explicitly enabled.
- [x] Define and implement quarantine expiration and cleanup.
- [x] Record `photo_upload_status`.
- [x] Preserve the report and identifiers when storage fails.
- [x] Resume a failed upload with the same idempotency key.
- [x] Ensure a successful retry updates the existing report instead of creating another.
- [x] Prevent private object keys from appearing in public tracking responses.

Phase 8D also uses an expiring processing lease, a hashed attempt token, a preallocated
pending object key, strict object-key validation, and a canonical non-photo submission
fingerprint. These prevent permanently stuck attempts, untraceable partial writes,
ordinary photo replacement, and silent idempotency payload conflicts.

### Required tests

- Report persists before storage attempt.
- EXIF metadata removal and orientation correction.
- Invalid image and failed-storage paths.
- Resumable upload with one report.
- Quarantine authorization and cleanup.
- Public response does not expose paths.

### Exit gate

- [x] Sanitized photographs are private.
- [x] Failed uploads are resumable and non-destructive.
- [x] A live processing lease prevents an interleaved retry from obtaining a second
      valid claim; expired leases recover safely.
- [x] Laravel storage and regression tests pass.

### Explicitly excluded

No Supabase Storage adapter, Cloud Tasks, or Cloud deployment.

---

## Phase 8E — Laravel AI Orchestration and Retry

**Branch:** `feature/phase-8e-laravel-ai-orchestration`

**Objective:** Make Laravel send stored evidence to FastAPI and safely persist advisory
results.

### Implementation tasks

- [x] Create a reusable `ProcessReportAi` action/service.
- [x] Atomically claim a report and prevent simultaneous duplicate processing.
- [x] Retrieve the actual stored sanitized photograph.
- [x] Send photograph, description, and GPS to FastAPI as multipart data.
- [x] Ignore all mobile-supplied AI classifications and confidence values.
- [x] Validate the complete FastAPI response schema before persistence.
- [x] Store image, NLP, GIS, fusion, version, hash, timing, and review results.
- [x] Populate `ai_possible_violation`; leave `official_violation_type` null.
- [x] Record attempt count, request identifier, timestamps, and operational errors.
- [x] Preserve reports and credentials when FastAPI is unavailable or invalid.
- [x] Reuse the same stored photograph for authorized retry.
- [x] Introduce a dispatching interface that can later be backed by Cloud Tasks.
- [x] Do not use `dispatchAfterResponse()` or an untracked background PHP process.

### Required tests

- Stored file is attached to FastAPI request.
- Client AI fields are ignored.
- Schema-invalid response is controlled.
- Successful result persistence.
- Timeout/offline failure preservation.
- Processing lock and retry idempotency.
- AI and official fields remain separate.

### Exit gate

- [x] Laravel-to-FastAPI inference passes locally.
- [x] Failure never removes the report or Tracking Token.
- [x] Authorized retry works without duplicate reports.
- [x] Laravel and FastAPI regression suites pass.

---

### Phase 8F-0 — Mobile Unclassified Contract Prerequisite

**Branch:** `fix/phase-8f-mobile-unclassified-contract`

**Objective:** Allow the Phase 8F mobile client to omit a citizen classification without
fabricating a category or changing the non-null legacy schema.

This is a safety prerequisite within Phase 8F, not a separate roadmap feature.

#### Contract

- [x] Use one centralized, server-owned `Unclassified` storage sentinel.
- [x] Reject clients that explicitly submit the internal sentinel.
- [x] Preserve all genuine legacy citizen-selectable categories.
- [x] Return null rather than the sentinel as a citizen selection.
- [x] Keep AI recommendations and official staff classification independent.
- [x] Show staff `Awaiting Staff Classification`.
- [x] Exclude the sentinel from citizen-category analytics and filters.
- [x] Keep omitted-category idempotent fingerprints deterministic.
- [x] Make no database-schema, mobile-runtime, or cloud change.

#### Exit gate

- [x] The existing string column can safely store the sentinel without migration.
- [x] Focused contract tests pass against isolated in-memory SQLite.
- [x] Full Laravel regression tests pass.
- [x] User manually verifies and merges Phase 8F-0 before Stage A begins.

---

## Phase 8F — Mobile Server-AI Submission and Polling

**Branch:** `feature/phase-8f-mobile-server-ai-flow`

**Objective:** Make the Android application capture and submit evidence while the server
owns inference.

Stage A implements the server-AI mobile flow while retaining the native TFLite
dependencies and artifacts. Their removal belongs only to the separately approved
Stage B parity/removal gate.

### Implementation tasks

- [x] Remove “Analyze Photo” as a required submission step.
- [x] Stop sending trusted AI prediction and confidence fields.
- [x] Generate one idempotency key per logical draft and reuse it for uncertain retries.
- [x] Securely retain the raw Tracking Token and Report Number.
- [x] Preserve camera, gallery, image compression, GPS, consent, history, and tracking.
- [x] Display report, AI-processing, and barangay-assignment states independently.
- [x] Poll Laravel with reasonable backoff and slow/stop at terminal states.
- [x] Use “Possible Violation,” never “Confirmed Violation,” before staff verification.
- [x] Add a development-only API diagnostic with base URL, reachability, HTTP status, and
      a safe error.
- [x] **Stage B only:** After server inference parity passes, remove unused TFLite initialization, native
      plugins, dependencies, and model bundling from the mobile runtime.

### Required tests

- Submission payload contains no trusted classification.
- Idempotency persists through draft retry.
- Tracking Token persistence and lookup.
- Polling state transitions and terminal behavior.
- Camera, gallery, GPS, history, and privacy regression tests.
- TypeScript, ESLint, Jest, Expo Doctor, and Android export/build validation.

### Exit gate

- [ ] A physical phone or emulator submits to Laravel without local inference.
- [ ] Tracking and polling work.
- [x] Mobile native AI dependency is removed only after server inference is verified.
- [x] Mobile validation suite passes.

### Phase 8F-R — Deferred Recovery-Discard Hotfix

**Future branch:** `fix/phase-8f-recovery-discard`

**Known physical-device defect:** `Discard Local Recovery` does not remove the saved
recovery entry on the tested Android device.

This follow-up is deliberately limited to reproducing and fixing explicit local recovery
deletion, verifying that its app-owned snapshot and journal entry are removed, and adding
focused regression coverage. It must not change report submission, tracking credentials,
server AI, GIS, branding, status workflows, TFLite removal, database schema, or cloud
deployment. The defect is deferred from the Stage A merge by user acceptance, but it
must be resolved and physically retested before the production release gate.

---

# Phase 9 — Supabase Migration

## Phase 9A — Supabase PostgreSQL Migration

**Branch:** `feature/phase-9a-supabase-postgres-migration`

**Objective:** Move durable relational data from SQLite to Supabase PostgreSQL.

### Implementation tasks

- [x] Create and verify a final SQLite backup.
- [x] Enable and verify PHP PostgreSQL support.
- [x] Configure the Supabase Session Pooler with secrets outside Git.
- [x] Prefer a dedicated Laravel application schema.
- [x] Validate every migration against PostgreSQL.
- [x] Build a controlled, repeatable import command.
- [x] Preserve users, password hashes, roles, reports, timelines, identifiers,
      verification evidence, and relationships.
- [x] Explicitly mark known seeded records as `is_test_data = true`.
- [x] Validate boolean, JSON, decimal, date, timestamp, index, and foreign-key behavior.
- [x] Verify hash uniqueness after import.
- [x] Do not run migrations automatically on every container startup.

### Exit gate

- [x] Record counts and relationships match the approved migration manifest.
- [x] Existing credentials and roles still work.
- [x] PostgreSQL Laravel regression tests pass.
- [x] Rollback/recovery procedure is documented and tested.

Phase 9A database activation is complete. The rollback procedure was tested against a
new isolated copy of the immutable SQLite backup without changing Supabase or the live
`.env`. The drill verified current migrations, integrity, relationships, credentials,
tracking, staff views, and HTTP startup. A real post-write rollback still requires a
new write freeze and reconciliation of every PostgreSQL-only row.

---

## Phase 9B — Supabase Private Storage

**Branch:** `feature/phase-9b-supabase-private-storage`

**Objective:** Replace private local photo storage with private Supabase Storage.

### Implementation tasks

- [x] Create a private report-photo bucket.
- [x] Implement the Supabase adapter behind the Phase 8D storage interface.
- [x] Upload only sanitized photographs.
- [x] Store private object keys in PostgreSQL.
- [x] Generate short-lived signed URLs only after staff authorization.
- [x] Never return signed URLs or object keys through public tracking.
- [x] Implement resumable storage-failure handling.
- [x] Implement quarantine cleanup.
- [x] Implement orphan detection and preserve unclassified files.
- [ ] Obtain an approved Santa Cruz municipal evidence-retention policy.
- [ ] Implement retention and deletion only from the approved municipal policy.
- [x] Reconcile partial database/storage failures.

### Exit gate

- [x] Public bucket access is disabled.
- [x] Authorized staff access works and expires.
- [x] Citizen tracking cannot access private photographs.
- [x] Storage integration and failure tests pass.

Phase 9B activated normal Supabase private photograph writes on 2026-08-09 after the
private-bucket canary, verified PostgreSQL backup, 16-object migration, controlled test
report, persisted server configuration, and HTTP health check passed. All 18 local files
remain available for recovery. Automated retention/deletion remains intentionally
deferred until a municipal evidence-retention policy is explicitly approved.

---

# Phase 10 — Reliable Cloud Processing and Deployment

## Phase 10A — Durable AI Task Dispatch Foundation

**Branch:** `feature/phase-10a-cloud-tasks-ai-processing`

**Objective:** Implement provider-neutral reliable asynchronous processing after durable
report and photograph storage. The first adapter used Google Cloud Tasks; Phase 10B
retains the same database leases, generations, status fields, and idempotent handler
while adding the approved Azure Queue provider.

### Implementation tasks

- [x] Create a Cloud Tasks adapter for the Phase 8E dispatch interface.
- [x] Create a protected Laravel processing endpoint.
- [x] Authenticate expected Cloud Task OIDC identity, audience, and service account.
- [x] Create a task only after report and photograph storage complete.
- [x] Return citizen identifiers without waiting for inference.
- [x] Record `task_creation_status`.
- [x] Enforce and test the application timeout hierarchy and CreateTask deadline.
- [x] Define the Azure Queue retry limit, exponential visibility delay, maximum dequeue
      count, quarantine path, and one-at-a-time worker contract locally.
- [ ] Verify those retry and quarantine settings against real Azure resources during the
      separately approved Phase 10B provisioning stages.
- [x] Make duplicate task delivery safe.
- [x] Preserve reports when task creation fails.
- [x] Add a recovery command/job for completed uploads with failed task creation and
      pending AI.
- [x] Record task-dispatch failures using dedicated fields equivalent to
      `task_creation_error_code = TASK_CREATION_FAILED` and a safe
      `task_creation_error_message`.
- [x] Keep task-creation errors separate from Phase 8E AI-processing errors and never
      misclassify either error domain as AI evidence review.

### Exit gate

- [x] Task delivery and duplicate delivery are idempotent.
- [x] Task creation failure is recoverable.
- [x] Citizen submission remains valid during queue failure.
- [x] Cloud Task integration tests pass using the guarded fake creator and verified
      identity test doubles; real queue delivery remains a Phase 10B/10C gate.

---

## Phase 10B — Secure Azure Container Apps Deployment

**Branch:** `feature/phase-10b-azure-secure-deployment`

**Objective:** Deploy public Laravel, an event-driven AI worker, and internal FastAPI on
Azure Container Apps using managed identities and least-privilege authorization.

**Approved evidence flow:**

```text
Mobile
→ public Laravel Container App
→ Supabase PostgreSQL and private Storage
→ Azure Queue Storage
→ event-driven Container Apps Job
→ Entra-protected Laravel task endpoint
→ internal Entra-protected FastAPI Container App
→ Laravel persistence
→ mobile status polling
```

MPDO barangay polygon integration remains Phase 13A and must not be folded into this
deployment phase.

### Implementation tasks

- [x] Add reproducible, non-root Laravel and FastAPI container definitions with no
      startup migrations or secret generation.
- [x] Add a provider-specific Azure Queue REST adapter using managed-identity bearer
      tokens; do not introduce the retired Azure Storage PHP SDK or Shared Key signing.
- [x] Require exact user-assigned identity client IDs and fixed resource audiences for
      every managed-identity token request.
- [x] Add an event worker that preserves Phase 10A generations and leases, owns Queue
      visibility retry, quarantines exhausted messages, and exits cleanly after handling
      a retry.
- [x] Define Applications-only Entra roles `Civiclear.AiTask.Invoke` and
      `Civiclear.FastApi.Invoke` and bind expected tenant, audience, role, client, and
      managed-identity principal claims.
- [x] Define local Bicep for Basic ACR, Consumption-only Workload Profiles v2 Container
      Apps, bounded Log Analytics, Key Vault, Queue Storage, user-assigned identities,
      exact queue-scope RBAC, private FastAPI ingress, and bounded scale-to-zero limits.
- [x] Add a GitHub-hosted remote-build fallback that requires an exact reviewed commit
      SHA, performs filename and credential checks, and produces SHA-tagged images with
      provenance and SBOM metadata.
- [x] Pass the immutable pre-build source gate and create the reviewed implementation
      commit after explicit approval.
- [x] Verify Azure for Students cost and quota readiness before creating any resource.
- [x] Create Azure resources, app registrations, roles, and assignments only after their
      separate approval gates; never self-escalate Entra permissions.
- [x] Populate first Key Vault secret versions only in the separately approved protected
      secret stage; use version-pinned references and never placeholder values.
- [x] Remotely build and inspect exact-commit images, record immutable digests, and deploy
      those digests only after explicit approval.
- [x] Prove Laravel public ingress, FastAPI internal-only routing, Entra authorization,
      queue send/receive/update/delete/quarantine, ACR pull, Supabase connectivity,
      probes, timeouts, and bounded concurrency.
- [x] After every required queue operation works with managed identity, separately
      approve disabling Storage Shared Key and retest the complete queue flow.
- [ ] Deploy and verify the non-URL `Authorization: Bearer` Tracking Token contract
      before production acceptance.

### Exit gate

- [x] Laravel is publicly reachable over HTTPS through Azure Container Apps.
- [x] FastAPI is unreachable from the public internet and rejects unauthorized internal
      inference requests.
- [x] Managed-identity Entra invocation succeeds for worker-to-Laravel and
      Laravel-to-FastAPI only.
- [x] Supabase and Azure Queue processing work without Shared Key or connection strings.
- [x] Health endpoints report safe liveness and readiness.
- [ ] Measured latency, cold starts, processing duration, Queue visibility, worker
      timeout, replica limits, logs, and student budget alerts are recorded.

---

## Phase 10C — Azure Cloud End-to-End Verification

**Branch:** `test/phase-10c-cloud-end-to-end-verification`

**Objective:** Verify the deployed citizen workflow outside the local network.

### Required evidence flow

```text
Mobile
→ public Laravel Container App
→ Supabase PostgreSQL
→ private Supabase Storage
→ Azure Queue Storage
→ event-driven Container Apps Job
→ protected Laravel endpoint
→ internal FastAPI Container App
→ Laravel persistence
→ mobile polling
```

### Exit gate

- [ ] Real mobile submission works outside local Wi-Fi.
- [ ] Report and sanitized photo persist.
- [ ] Task and protected endpoint execute.
- [ ] Image, NLP, GIS, and fusion results persist.
- [ ] Tracking lookup works.
- [ ] AI failure does not lose the report.
- [ ] No-detection is controlled.
- [ ] A representative positive image is recorded when available.
- [ ] Localhost, USB forwarding, mobile hotspot, and Cloudflare Tunnel are not required.

---

# Phase 11 — Staff Verification

## Phase 11A — Staff Verification Workflow

**Branch:** `feature/phase-11a-staff-verification-workflow`

**Objective:** Make staff verification—not AI—the source of official classification.

### Implementation tasks

- [ ] Preserve AI recommendation and confidence.
- [ ] Present the completed AI category automatically; staff normally verifies or
      rejects it instead of manually initiating AI or re-entering the category.
- [ ] Populate `official_violation_type` from the reviewed AI category only when staff
      verifies it; allow an explicit correction when the evidence shows the AI result is
      wrong.
- [ ] Record `verification_status`, `verified_by`, and `verified_at`.
- [ ] Record AI-versus-staff agreement/correction for later model evaluation.
- [ ] Allow authorized invalid and duplicate classifications.
- [ ] Preserve DILG monitoring, manual routing, and AI retry responsibilities.
- [ ] Enforce effective-barangay and role authorization.
- [ ] Add timeline entries for every verification decision.

### Exit gate

- [ ] AI runs automatically after submission but cannot automatically verify a report.
- [ ] Assigned barangay staff can verify only authorized reports.
- [ ] DILG routing remains functional.
- [ ] Authorization, verification, and timeline tests pass.

---

# Phase 12 — Operational GIS and Official Analytics

## Phase 12A — Operational GIS and Official Analytics

**Branch:** `feature/phase-12a-gis-official-analytics`

**Objective:** Separate operational monitoring from official verified statistics.

### Operational map

The map may show submitted, AI-pending, unverified, verified, assigned, rejected,
outside-coverage, and optionally test reports. Each marker must identify its validation
state.

### Official statistics rule

Do not require `report_status = Verified`; valid reports must remain counted after they
become Assigned, In Progress, Action Taken, Resolved, or Closed.

Use equivalent verification evidence:

```text
is_test_data = false
verified_at IS NOT NULL
official_violation_type IS NOT NULL
verification_status = Valid Violation
inside_supported_jurisdiction = true
report_status != Rejected
is_duplicate = false
```

### Exit gate

- [ ] Operational markers distinguish lifecycle and verification states.
- [ ] Verified Assigned, Resolved, and Closed reports remain in official totals.
- [ ] Rejected, duplicate, outside-jurisdiction, unverified, and test reports are
      excluded.
- [ ] Dashboard totals match direct verified-record queries.
- [ ] GIS and analytics query tests pass.

---

# Phase 13 — Barangay Boundary Integration

## Phase 13A — Validated Barangay Polygon Detection

**Branch:** `feature/phase-13a-barangay-boundary-integration`

**Status:** **BLOCKED** until validated Polygon or MultiPolygon data is available.

Do not create or begin this branch while the required dataset is unavailable.

### Required work when unblocked

- [ ] Obtain all Santa Cruz barangay polygons from a validated source.
- [ ] Record source, version, validation method/date, validator, and SHA-256.
- [ ] Validate GeoJSON structure, geometry, winding/edge behavior, and barangay names.
- [ ] Implement point-in-polygon detection and boundary-edge handling.
- [ ] Test known coordinates for every barangay.
- [ ] Record automatic/manual assignment source.
- [ ] Preserve authorized staff correction.
- [ ] Never use nearest barangay hall points as jurisdiction.

Until completion:

```text
Municipality validation: Available
Exact barangay detection: Unavailable
Barangay routing: Manual through DILG
```

---

# Phase 14 — Production Hardening, Municipal Acceptance, and Thesis Demonstration

## Phase 14A — Production Hardening, Municipal Acceptance, and Thesis Demonstration

**Branch:** `release/phase-14a-production-hardening`

**Objective:** Produce a secure, measured, reproducible thesis demonstration release
and an operationally governed candidate for an authorized Santa Cruz municipal pilot.

The thesis demonstration tag does not by itself authorize collection of real citizen
reports in production. Real municipal use requires every final release gate below,
written municipal acceptance, assigned operational ownership, and the mandatory
evidence-retention and deletion gate.

### Mandatory municipal evidence-retention and deletion gate

This gate may be completed after the remaining development phases, but it must pass
before public production launch or collection of real citizen evidence. Automatic
deletion must remain disabled until the gate is complete.

- [ ] Identify the authorized Santa Cruz system owner, records custodian, and Data
      Protection Officer or equivalent privacy authority.
- [ ] Obtain a written municipal policy defining retention periods for submitted,
      verified, invalid, duplicate, outside-jurisdiction, test, and appealed reports.
- [ ] Define who may authorize deletion and whether approval by more than one authorized
      role is required.
- [ ] Define legal-hold, active-investigation, complaint, appeal, and audit exceptions
      that prevent deletion.
- [ ] Define how retention applies to Supabase objects, PostgreSQL records, local
      rollback copies, logical backups, exports, quarantined files, and audit logs.
- [ ] Approve the citizen privacy notice and the process for authorized access,
      correction, preservation, and deletion requests.
- [ ] Implement a dry-run retention report that changes nothing and identifies every
      candidate by internal record identity without exposing evidence publicly.
- [ ] Implement deletion only after policy approval, with authorization, idempotency,
      audit history, failure reconciliation, and protection against deleting active or
      legally held evidence.
- [ ] Test retention and deletion first with disposable test data, including partial
      storage/database failure and recovery scenarios.
- [ ] Obtain written municipal acceptance of the implemented policy and test evidence.

If this gate is incomplete, CIVICLEAR may be demonstrated with approved test data, but
it must not be represented as approved for unattended public production use.

### Implementation tasks

- [ ] Run all Laravel, FastAPI, mobile, and deployed end-to-end tests.
- [ ] Test backup and restoration.
- [ ] Verify private Storage, token privacy, idempotency, rotation/replay, task recovery,
      and OIDC.
- [ ] Measure model startup, inference, queue, upload, polling, and total processing
      latency.
- [ ] Review Azure Container Apps, Queue Storage, ACR, Key Vault, and Supabase limits,
      connections, regions, student-credit eligibility, and costs.
- [ ] Complete model and Ultralytics licensing review.
- [ ] Complete the mandatory municipal evidence-retention and deletion gate.
- [ ] Remove remaining obsolete phone-side AI code and environment values.
- [ ] Verify UTF-8 across source, documentation, labels, JSON, GeoJSON, and responses.
- [ ] Verify official statistics exclude test data.
- [ ] Prepare deployment/startup documentation, architecture diagram, evidence matrix,
      limitations, recovery procedure, and panel demonstration script.
- [ ] Create and verify the final backup.

### Final release gate

- [ ] Real mobile submission and tracking work.
- [ ] Internal FastAPI and Azure Queue recovery work.
- [ ] Reports survive AI and infrastructure failures.
- [ ] Staff verification and official analytics are correct.
- [ ] Security and privacy review is complete.
- [ ] Municipal retention/deletion policy is approved, implemented, and tested.
- [ ] Santa Cruz operational ownership and production acceptance are documented.
- [ ] Known GIS limitation is stated honestly.
- [ ] Demonstration evidence and script are complete.

After final review, create:

```text
v1.0.0-thesis-demo
```

## Final branch order

```text
1.  chore/phase-8a-baseline-safety
2.  feature/phase-8b-report-schema-tracking-security
3.  feature/phase-8c-server-side-ai-inference
4.  feature/phase-8d-durable-report-photo-pipeline
5.  feature/phase-8e-laravel-ai-orchestration
6.  feature/phase-8f-mobile-server-ai-flow
7.  feature/phase-9a-supabase-postgres-migration
8.  feature/phase-9b-supabase-private-storage
9.  feature/phase-10a-cloud-tasks-ai-processing
10. feature/phase-10b-cloud-run-secure-deployment
11. test/phase-10c-cloud-end-to-end-verification
12. feature/phase-11a-staff-verification-workflow
13. feature/phase-12a-gis-official-analytics
14. feature/phase-13a-barangay-boundary-integration
    BLOCKED until validated polygons are available
15. release/phase-14a-production-hardening
```

## Immediate next action

Do not create a branch until the three unexplained deletions are resolved.

When the worktree is understood and safe:

```powershell
git switch main
git status
git switch -c chore/phase-8a-baseline-safety
```

Begin only the Phase 8A checklist. Do not combine future phase work into the baseline
branch.
