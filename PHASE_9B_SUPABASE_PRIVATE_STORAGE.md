# Phase 9B — Supabase Private Storage

## Scope and approval gates

Phase 9B moves sanitized report photographs from the existing private local disk to a
private Supabase Storage bucket. PostgreSQL remains the authoritative relational
database established by Phase 9A.

The approved stages are intentionally separate:

1. Stage 0 — read-only readiness and inventory.
2. Stage 1 — local implementation and isolated tests.
3. Stage 2 — private bucket verification and a disposable canary.
4. Stage 3 — PostgreSQL recovery backup and verified photograph migration.
5. Stage 4 — controlled cutover validation while normal writes remain blocked.
6. Stage 5 — normal write enablement after separate approval.

Stage 1 does not create a bucket, configure real credentials, upload an object, migrate
a database row, change the active runtime driver, or delete a local photograph.

## Stage 0 baseline

- Starting branch: `main`
- Starting commit: `f056527edea4aa8e36e50f18b1b7ef0c05f8dbf4`
- Phase 9A is present and the worktree was clean.
- 28 reports existed in PostgreSQL.
- 16 uploaded photograph rows referenced `report_photos`.
- All 16 referenced local objects existed.
- No photograph row had a pending key or failed upload status.
- 17 local private files existed, including one unreferenced file.
- The unreferenced file must remain untouched until it is explicitly classified.

## Storage selection contract

`REPORT_PHOTO_STORAGE_DRIVER` is a trusted server-only selector for new writes.

Approved values:

- `local`
- `supabase`

The default is `local`. Unknown values fail closed. Selecting `supabase` also fails
closed when any required server-side S3 setting is incomplete or unsafe.

Persisted disk identities are separate from that selector:

- `report_photos` — existing private local objects
- `supabase_report_photos` — private Supabase objects

Changing the new-write selector never changes where an existing row is read. Staff
streaming, AI processing, retry cleanup, and later migration logic must resolve an
existing photograph through its persisted `photo_storage_disk` value.

## Supabase S3 configuration contract

Real values belong only in ignored server environment configuration:

- `SUPABASE_STORAGE_S3_ACCESS_KEY_ID`
- `SUPABASE_STORAGE_S3_SECRET_ACCESS_KEY`
- `SUPABASE_STORAGE_S3_REGION`
- `SUPABASE_STORAGE_S3_BUCKET`
- `SUPABASE_STORAGE_S3_ENDPOINT`

The bucket name is fixed to `civiclear-report-photos`. The endpoint must use HTTPS and
the exact direct-storage form:

```text
https://<20-character-project-ref>.storage.supabase.co/storage/v1/s3
```

Path-style access is mandatory. Generated Supabase S3 credentials are privileged,
server-only credentials and must never be placed in Git, the mobile app, FastAPI,
responses, logs, or documentation.

## Object safety

- Only opaque Phase 8D object keys are accepted.
- Existing objects are checked before an upload; a detected collision stops the write.
- An upload is accepted only after its complete byte length and SHA-256 digest match.
- The implementation does not claim atomic create-if-absent support.
- Objects receive the sanitized MIME type and `Cache-Control: private, no-store`.
- The upload hook removes unsupported per-object ACL headers; privacy is enforced by
  the separately verified private bucket.
- Original citizen filenames are not stored in object metadata.
- Local source photographs are never deleted during migration.
- Supabase bucket versioning is not assumed.

## Staff access

The existing Laravel-authorized streaming route remains the normal staff access path
and now supports mixed local/Supabase rows.

A separate staff-authorized route may issue a short-lived Supabase signed redirect for
remote objects. It does not return a signed URL in JSON. The redirect carries:

```text
Cache-Control: private, no-store, max-age=0
Referrer-Policy: no-referrer
```

Public tracking continues to expose neither object keys, storage-disk identities,
private streams, nor signed URLs.

## Stage 1 verification

- Composer dependency resolution was checked before installation.
- `league/flysystem-aws-s3-v3` and its required AWS SDK dependencies are installed.
- The active runtime remains `local` because the ignored environment has no explicit
  Phase 9B selector.
- Phase 9B isolated storage and access tests pass.
- Phase 8D durable-photo regression tests pass.
- Phase 8E server-AI orchestration regression tests pass.

## Stage 2 disposable canary

Stage 2 was separately approved after Stage 1 passed. The guarded command is:

```text
php artisan phase9b:verify-storage-canary
php artisan phase9b:verify-storage-canary --execute
```

The first command validates server-side configuration and writes nothing. The
`--execute` form is permitted only after the exact private bucket and its restrictions
have been verified. It creates one generated 2-by-2 PNG under a random opaque report
key and then verifies:

- complete stored-byte integrity;
- denial through the public object URL;
- short-lived signed access;
- `private, no-store` response caching;
- denial after signed-link expiry; and
- deletion of the generated object.

The command never uses citizen evidence, never changes the active new-write selector,
and never changes a PostgreSQL photograph reference. If a transport failure might have
occurred after upload, cleanup still inspects and removes the generated opaque key. A
cleanup failure stops the stage and requires manual bucket inspection.

Stage 2 completed on 2026-08-09. The private bucket restrictions were confirmed and
the executed canary passed upload integrity, public denial, signed access, expiry,
private cache policy, and verified cleanup. No canary object remains.

## Stage 3 recovery backup and photograph migration

Stage 3 was separately approved after Stage 2. Before any PostgreSQL reference was
changed:

- Laravel entered maintenance mode;
- PostgreSQL 17.10 `pg_dump` created a custom-format logical backup outside Git;
- PostgreSQL 17.10 `pg_restore --list` verified the backup catalog;
- the backup SHA-256 was independently rechecked;
- the backup recorded 28 reports, 16 uploaded photographs, 16 local references, and
  zero Supabase references; and
- the frozen photograph-reference digest was recorded and matched before execution.

The backup is 62,664 bytes with SHA-256:

```text
af1b877a7f0e923cce956afc78bc682328d8304ef9392e0c3d984dca7d6d1703
```

The no-write migration inspection passed with 17 local files, 16 referenced local
photographs, no preexisting remote collisions, and one unclassified local orphan.

The guarded migration then completed with:

- 16 newly copied and full-content-verified Supabase objects;
- zero reused preexisting remote objects;
- 16 PostgreSQL references switched to `supabase_report_photos` only after verification;
- zero remaining local PostgreSQL references;
- zero unknown uploaded-disk identities;
- zero pending photograph keys;
- all 17 local files retained; and
- the single unclassified local orphan retained without upload or deletion.

The active new-write selector remains `local`, Laravel remains in maintenance mode,
and normal writes remain blocked. Stage 4 controlled cutover is not authorized by the
Stage 3 approval.

## Stage 4 controlled cutover validation

Stage 4 was separately approved and completed on 2026-08-09 while Laravel remained in
maintenance mode. The guarded validation used the normal Laravel mobile-report pipeline
with the trusted process-only new-write selector set to `supabase`. The resulting report
was marked as test data only by the server after its controlled identity was verified.

The controlled report was `RCV-2026-0029`. Validation confirmed:

- exactly one new controlled test row was created;
- its sanitized photograph was written to `supabase_report_photos`;
- complete remote byte integrity matched the persisted SHA-256 digest;
- an independently verified local rollback copy was retained;
- authorized staff streaming succeeded and unauthenticated access was denied;
- the signed redirect remained private and public tracking exposed no storage details;
- all 17 uploaded photograph rows referenced Supabase Storage;
- all 18 local files were retained, including the original unclassified orphan; and
- normal application writes remained blocked by maintenance mode.

The controlled AI attempt ended in the existing safe `failed` state. This did not affect
report durability, photograph integrity, storage privacy, or migration parity. Stage 5
normal-write enablement requires separate explicit approval.

## Stage 5 normal-write enablement

Stage 5 received separate explicit approval and completed on 2026-08-09. Before
maintenance mode was lifted:

- the trusted `supabase` new-write selector and required S3 settings were persisted only
  in ignored server environment configuration;
- the temporary PowerShell overrides were removed;
- Laravel reloaded the persisted configuration successfully;
- the Stage 4 validation was repeated without creating another report;
- all 17 uploaded photograph rows still referenced verified Supabase objects; and
- all 18 local rollback files remained present, including the original orphan.

Normal CIVICLEAR writes were enabled at:

```text
2026-08-09T07:13:35.2711744Z
```

Laravel left maintenance mode successfully and `GET /up` returned HTTP 200. The health
check did not create a report or modify evidence. Local source photographs were not
deleted.

After this point, rollback is not a simple global selector change. Any photographs
created only in Supabase after the activation timestamp must first be copied completely
back to private local storage and verified by byte size and SHA-256. PostgreSQL storage
references must then be reconciled transactionally while writes are frozen before the
trusted selector can return to `local`.

## Final verification and dependency note

- Phase 9B focused storage and cutover tests passed with 67 assertions.
- The complete Unit suite passed with 33 tests and 154 assertions.
- The complete Feature suite passed with 100 tests and 744 assertions.
- Composer validation passed.
- The only new direct Composer dependency is `league/flysystem-aws-s3-v3`; Laravel,
  PHP, FastAPI, mobile, and unrelated direct dependency constraints were not upgraded.
- Repository hygiene found no tracked `.env`, database dump, backup manifest, citizen
  photograph, real project reference, S3 credential, Tracking Token, or signed URL.

The final Composer audit reported six advisories against the pre-existing
`league/commonmark` 2.8.2 package. That package was not changed by Phase 9B. Resolving
the advisories requires a separately reviewed dependency update to 2.9.0 or later and
must not be hidden inside this storage migration.
