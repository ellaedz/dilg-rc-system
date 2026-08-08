# Phase 8E — Laravel AI Orchestration and Retry

## Baseline

- Starting branch: `main`
- Complete starting commit: `38afd610b763dc81ad5dd2bafe5f878cc6b3eae2`
- Implementation branch: `feature/phase-8e-laravel-ai-orchestration`
- Planned commit: `feat: add Laravel AI orchestration and retry`

The final commit SHA is reported after the commit is created. It is deliberately not
self-referenced from this document.

## Scope

Phase 8E replaces the transitional JSON-only Laravel AI call with one server-owned
orchestration path. Laravel verifies and reads the Phase 8D sanitized private
photograph, sends it with the report description and coordinates to FastAPI, validates
the complete Phase 8C result, and persists advisory AI evidence.

This phase does not change the mobile application, provision queues, migrate to
Supabase, deploy Cloud Run, expose FastAPI publicly, add Google OIDC, or fabricate
barangay polygons.

## Authority

Laravel remains authoritative for report durability, Tracking Tokens, public APIs,
official verification, report status, and barangay routing. FastAPI provides advisory
image, NLP, GIS, and fusion evidence. Its GIS result is stored separately and never
overwrites Laravel routing fields.

New reports continue accepting the old mobile AI fields for transport compatibility,
but those fields are ignored. Only the validated server result can populate new AI
evidence. AI never populates `official_violation_type`, `verified_by`, or `verified_at`.

## Processing state

The controlled states are:

```text
pending
processing
completed
failed
```

Normal transitions are:

```text
pending -> processing
processing -> completed
processing -> failed
failed -> processing                    authorized DILG retry only
expired processing -> processing        authorized DILG retry only
```

`completed` is terminal for ordinary Phase 8E processing. A live lease cannot be
claimed twice. Attempts increment only after a claim succeeds.

Every claim has a random in-memory owner token. Only its SHA-256 hash is stored. The
lease is at least 15 seconds longer than the configured HTTP timeout and defaults to 60
seconds. Completion and failure updates require the active hash, preventing a late
worker from overwriting a newer attempt.

## Dispatch

`ReportAiDispatcher` separates report submission from processing transport.
`InlineReportAiDispatcher` is intentionally synchronous and calls `ProcessReportAi`
directly only after the report transaction commits and the photograph reaches
`uploaded`.

It does not create a Laravel job, require a worker, use `dispatchAfterResponse()`, spawn
a process, or claim cloud durability. Phase 10A can replace the dispatcher with Cloud
Tasks while retaining the processing action.

Anonymous replay is not an AI retry endpoint. It returns completed or failed AI state
without starting another attempt. A replay that successfully recovers a previously
failed Phase 8D photograph upload may start the first AI attempt only when the report
has never been claimed for AI.

## Private evidence

`ProcessReportAi` uses `PrivateReportPhotoStorage`; it does not construct a public URL
or bypass the opaque object-key rules. Eligibility requires:

- an `uploaded` photograph;
- the configured private disk;
- an approved JPEG or PNG sanitized MIME type;
- bounded persisted size;
- valid persisted SHA-256;
- an existing private object;
- valid report description and coordinates.

One bounded stream verifies the exact byte count and SHA-256 and is then closed. A
second stream starts at byte zero for multipart upload and is closed in guaranteed
cleanup. Integrity failure preserves the object for authorized investigation.

The local filesystem roots support process-local E2E overrides through
`REPORT_PHOTO_LOCAL_ROOT` and `REPORT_PHOTO_QUARANTINE_LOCAL_ROOT`. Empty or absent
values retain the Phase 8D defaults.

## FastAPI contract

Laravel sends:

```text
POST /v1/predict/multimodal

image
text_report
latitude
longitude
```

The request includes a non-secret `X-Request-ID`, generic evidence filename, stored
sanitized MIME type, bounded connect/total timeouts, and no automatic retry. It never
sends a Tracking Token, token hash, idempotency credential, storage key, local path,
contact number, staff identity, or mobile AI prediction.

Successful HTTP responses must contain valid:

```text
image
text
gis
fusion
models
timing
review
```

Validation uses the implemented Phase 8C classes, statuses, decision sources, model
hash format, pixel-coordinate system, detection bound, and approved AI review reasons.
Text and fusion predictions may be null. Detection boxes must fit the returned original
dimensions, and those dimensions must match the stored sanitized photograph.

Responses are read through a configured byte bound before JSON decoding. Only a fully
validated, allowlisted normalized result is persisted. No partial success state is
written.

## Persistence

The additive migration records:

- attempt count and non-secret request ID;
- hashed processing owner and lease timestamps;
- last attempt timestamp;
- server image prediction, confidence, status, and detections;
- separate advisory FastAPI GIS result;
- validated model metadata and hashes;
- validated timings;
- complete approved AI manual-review reasons.

Existing compatibility columns retain normalized image, text, fusion, and model values.
`ai_raw_response` contains only the bounded, validated normalized snapshot.

## Controlled errors

Stable local categories distinguish:

- photograph unavailable, oversized, unreadable, or integrity-mismatched;
- FastAPI request rejection;
- access rejection;
- endpoint absence;
- timeout;
- rate limiting;
- internal failure;
- unavailability;
- malformed operational-error response;
- malformed JSON;
- excessive response;
- invalid success schema;
- stored/returned dimension mismatch;
- stale lease ownership;
- unexpected orchestration failure.

Only safe generic messages are stored. Reports, credentials, timeline entries, and
private photographs survive all AI failures.

## Retry authorization

The retry route remains inside authenticated `dilg.admin` middleware. It uses the same
`ProcessReportAi` action and same stored object. Barangay staff and anonymous users
cannot trigger retry. Retry never creates a report, replaces a photograph, or issues a
Tracking Token.

## Verification

The focused Phase 8E suite covers multipart evidence, distrust of mobile AI,
success/failure persistence, HTTP classification, malformed and excessive responses,
nullable Phase 8C predictions, dimension matching, public replay behavior, staff
authorization, live and expired leases, stale workers, integrity failure, and public
privacy.

Completed verification:

- focused Phase 8E suite: 24 tests, 207 assertions;
- full Laravel suite: 87 tests, 689 assertions;
- FastAPI suite: 25 tests passed (45 dependency deprecation warnings);
- mobile Jest suite: 3 suites and 11 tests passed;
- mobile TypeScript and ESLint checks: passed;
- Composer manifest validation: passed;
- Python dependency check: no broken requirements;
- Laravel Pint on all changed and new PHP files: passed;
- Expo Doctor: no repository-local executable was available, so no dependency was
  installed and this informational check remains for Phase 8F.

An isolated migration exercise used a unique SQLite database outside the repository.
It applied all migrations, rolled Phase 8E back, reapplied it, and confirmed the
migration status. The exact temporary directory was verified and removed afterward.

An isolated local E2E exercise used unique SQLite and private-photo roots outside the
repository plus the actual Laravel and FastAPI processes. It verified successful
multipart inference and persistence, public opaque-token tracking, offline failure
preservation, and an authorized retry of the same report and private photograph after
FastAPI recovery. The retry incremented the attempt count without changing the report,
photo object, Tracking Token hash, or official verification fields. Both temporary
services were stopped and the exact temporary directory was removed.

The real development database, normal private-photo roots, and `.env` were not changed.

## Remaining Phase 8F work

Repository inspection confirms:

- mobile still uses an environment-configured Laravel URL;
- mobile does not generate and retain one stable Idempotency-Key per draft;
- mobile tracking UI and storage still assume the old sequential identifier and
  uppercase values instead of preserving the case-sensitive opaque token;
- mobile still contains and executes transitional on-device TFLite inference;
- mobile does not poll Laravel for completed server-side AI results.

Phase 8F removes the transitional device-AI requirement and makes the mobile app use
Laravel server-AI status consistently.

## Recovery

Before merge, abandon Phase 8E by switching back to `main`; do not delete the Phase 8A
backup. The migration is additive and has a tested `down()` path. No cloud resource is
created in this phase.

Local E2E verification uses a unique database and storage roots outside the repository.
Cleanup may remove only those exact positively verified temporary paths and only after
stopping the processes started for that verification.
