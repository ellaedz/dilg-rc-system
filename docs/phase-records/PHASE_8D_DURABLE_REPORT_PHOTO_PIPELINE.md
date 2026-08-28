# Phase 8D — Durable Report and Private Photo Pipeline

## Baseline and scope

- Starting branch: `main`
- Complete starting commit: `34c24416c140d16a408c4e475f6fd5ecd521e8ac`
- Implementation branch: `feature/phase-8d-durable-report-photo-pipeline`
- Planned commit: `feat: add durable private photo pipeline`
- Pre-commit worktree: Phase 8D changes only; final status is verified before commit
- Excluded: Supabase adapters, Cloud Tasks, cloud deployment, stored-photo FastAPI
  orchestration, mobile TFLite removal, and legacy-photo deletion

The Phase 8A backup was confirmed at
`C:\Users\63923\Desktop\database\backups\DILG-RC\phase-8a\20260728-175019`
before the branch was created.

## Runtime and request boundary

The target CLI is PHP 8.2 with GD 2.1-compatible support, Fileinfo, and EXIF enabled.
JPEG, PNG, and WebP decode/encode functions were verified. PHP
`upload_max_filesize` and `post_max_size` are both 40 MiB; the application photograph
limit is 5 MiB, leaving room for multipart overhead and application-controlled failure.
If PHP rejects the complete body before Laravel receives it, the mobile report route
returns safe HTTP 413 code `REQUEST_BODY_TOO_LARGE` and does not claim that a report
was created.

Configured image limits are:

| Limit | Value |
| --- | ---: |
| Upload bytes | 5 MiB |
| Width | 8,000 pixels |
| Height | 8,000 pixels |
| Total pixels | 20,000,000 |
| JPEG quality | 85 |
| PNG compression | 6 |

## Sanitation contract

The server validates bounded bytes with Fileinfo and decoded image information rather
than trusting filenames or multipart MIME alone. It rejects unsupported, malformed,
truncated, MIME-mismatched, excessive, and detectable multi-frame input. APNG `acTL`
and animated WebP `VP8X`/`ANIM`/`ANMF` signals are rejected.

All EXIF orientations 1–8 are applied. A new true-color canvas is created, so original
EXIF, GPS, thumbnails, comments, profiles, and ancillary metadata are not copied.
Output is deterministic by input family:

- JPEG input → sanitized JPEG on the configured neutral background
- static WebP input → sanitized JPEG on the configured neutral background
- PNG input → sanitized PNG with intentional alpha preservation
- animated or unsupported input → controlled validation failure

GD resources and buffers are released on success and exceptions. Final SHA-256,
dimensions, MIME type, and byte size are calculated from the newly encoded output.

## Private storage and legacy separation

`PrivateReportPhotoStorage` provides opaque-key creation, private writes, streams,
existence checks, and targeted deletion. `LocalPrivateReportPhotoStorage` uses the
`report_photos` disk rooted under `storage/app/private/report-photos`. Keys match a
strict `reports/{shard}/{43-character-token}.{jpg|png}` pattern and contain no Report
Number, internal ID, filename, contact information, or public URL.

New private keys use `photo_object_key`. Legacy `image_path` is left unchanged and is
not interpreted as a private key, migrated, deleted, or exposed through the new
storage abstraction. Existing public legacy files require a separately approved
controlled migration.

The optional quarantine disk is private and disabled by default because sanitation is
performed in memory. `php artisan photos:purge-quarantine` deletes only expired,
strictly allowlisted keys under the quarantine prefix. It reports counts without paths.

## Report-first flow and status machine

Non-photo fields and the Idempotency-Key are validated first. Laravel then commits the
report, Report Number, tracking credentials, canonical submission fingerprint, and one
initial timeline before image decoding, filesystem I/O, or transitional AI calls.

Normal photo transitions are:

```text
not_provided -> processing
pending -> processing
failed_validation -> processing
failed_storage -> processing
processing -> uploaded
processing -> failed_validation
processing -> failed_storage
```

`uploaded` is terminal for ordinary replay. Replaying the identical photo returns the
existing result without writing again; a different photo returns HTTP 409
`PHOTO_REPLACEMENT_NOT_ALLOWED`.

Response behavior is:

- HTTP 201: newly committed report, including controlled photo failure
- HTTP 200: completed idempotent replay
- HTTP 202: another valid photo-processing lease is active
- HTTP 409: conflicting non-photo payload or prohibited photo replacement
- HTTP 413: complete request exceeds the effective PHP body limit
- HTTP 422: non-photo validation fails before report creation

Stable photo errors include upload, unsupported type, MIME mismatch, malformed decode,
size/dimension, multi-frame, sanitation, storage, finalization, and lease failures.
Only safe error codes/messages are returned.

## Lease, retry, and compensation

An attempt atomically claims the report with a random token whose SHA-256 is stored,
plus start/expiry timestamps. The default lease is five minutes. A live lease prevents
a second valid claim and returns HTTP 202. An expired lease is reclaimable.

After sanitation, an opaque pending key is associated with the current report/token
before filesystem write. Only the current unexpired token may finalize. On storage or
database finalization failure, the service deletes exactly that attempt's key. If
targeted deletion fails, the pending key and compensation state remain traceable; the
next authorized retry cleans it before allocating a new key. No database lock is held
during decoding, encoding, hashing, storage I/O, or AI calls.

A canonical hash of durable non-photo inputs prevents the same Idempotency-Key from
silently changing description, classification, location, timestamp, accuracy, or
contact data. Possession of the idempotency key is not a staff-edit credential.

The current pre-Phase-8F mobile source does not yet send an `Idempotency-Key` header.
The Laravel API fully supports safe replay when a client supplies one, and retains the
Phase 8B fallback for transitional compatibility, but a response-lost mobile request
cannot recover a server-generated fallback key. Phase 8F must generate and retain one
stable high-entropy key per mobile draft before enabling automatic mobile retry.

## Access and response privacy

Anonymous submission and tracking responses expose only photo status, availability,
and safe submission-time error information. They never expose:

- private or pending object keys;
- storage disk names;
- `image_path`;
- filesystem paths or URLs;
- processing token hashes;
- quarantine references.

Staff evidence is streamed by report identity through an authenticated route. DILG
administrators may read all private evidence. Barangay staff are limited to their
effective assigned barangay. Responses use the sanitized MIME type,
`X-Content-Type-Options: nosniff`, private/no-store caching, and a generic filename.

## Phase boundary

The existing transitional AI service is not redesigned. It runs only after a supplied
photo is uploaded, or for a compatibility submission that provides no photo. Failed
photo processing does not call AI. Phase 8E remains responsible for retrieving the
stored sanitized photograph and sending it to FastAPI as validated multipart data.

## Verification

Focused Phase 8D coverage includes:

- additive schema and legacy-path separation;
- all EXIF orientations with pixel-order assertions;
- JPEG/PNG/WebP sanitation and PNG alpha;
- metadata removal;
- malformed, mismatched, oversized, excessive, APNG, and animated WebP rejection;
- private opaque keys and traversal rejection;
- report-first storage failure;
- failed-validation and failed-storage retries;
- identical replay and replacement conflict;
- non-photo idempotency conflict;
- live and expired processing leases;
- partial-write traceability and cleanup;
- staff authorization and private streaming;
- public response privacy;
- quarantine expiration cleanup;
- safe request-body HTTP 413 behavior.

The focused Phase 8D suite passes with 32 tests and 254 assertions. The full Laravel
suite passes with 63 tests and 474 assertions. The unchanged FastAPI suite passes with
25 tests; `pip check` reports no broken Python requirements. Composer validation and
Pint checks on all Phase 8D PHP files pass.

A dedicated temporary SQLite database also passed migration fresh, Phase 8D rollback,
and re-application. The temporary database was deleted afterward. Final diff and
worktree checks are repeated before commit.

## Recovery

Before merge, abandon Phase 8D by switching back to `main`; do not delete the Phase 8A
backup. Phase 8D has no cloud resource or deployed service to tear down. Private test
disks use fakes, and no test touches the real application SQLite database.

The final commit SHA is reported only after commit creation and is intentionally not
self-referenced in this document.
