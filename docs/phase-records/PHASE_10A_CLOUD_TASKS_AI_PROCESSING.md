# Phase 10A — Cloud Tasks AI Processing

## Stage 1 scope

Phase 10A Stage 1 adds a locally testable Google Cloud Tasks dispatch path without
provisioning or invoking real Google Cloud resources. The safe default remains:

```text
REPORT_AI_DISPATCHER=inline
```

The `cloud_tasks` dispatcher must not be enabled until Phase 10B provisions and verifies
the queue, HTTPS handler URL, service accounts, IAM grants, and queue-level overrides.

No Phase 10A migration has been applied to the live `civiclear` PostgreSQL schema during
Stage 1.

## Authority boundaries

- Laravel remains the report, credential, dispatch, and AI-state authority.
- Supabase PostgreSQL and private Storage remain the durable data authorities.
- FastAPI remains advisory and has no database or Google Cloud credentials.
- Mobile never receives Google resource names, task IDs, generations, or task errors.
- Staff verification remains the only source of official violation classification.
- Task-creation errors and AI-processing errors use separate database fields.

## Delivery semantics

Application correctness does not assume exactly-once execution, unlimited retry, or
guaranteed eventual success. Duplicate delivery and duplicate execution are harmless.
PostgreSQL generation and lease state remains authoritative.

The task payload contains only:

```json
{
  "version": "v1",
  "report_id": 123,
  "task_generation": 1
}
```

It contains no photograph, Tracking Token, Report Number, citizen data, storage key,
model result, credential, or Google secret.

## Task creation state

Approved states are:

```text
not_started
creating
created
failed
uncertain
```

The initial dispatch uses generation `1`. A transport interruption or timeout with an
unknown CreateTask result retains the same generation and deterministic task ID. A staff
retry or approved stale-created recovery increments the generation and derives a new ID.
Public submission replay never increments the generation.

The deterministic task ID is lowercase SHA-256 over a versioned, server-owned purpose,
the internal report ID, and generation. Sequential IDs, Report Numbers, timestamps, and
citizen identifiers are not used as task IDs.

`ALREADY_EXISTS` reconciles recent use of that deterministic task ID. It does not prove
that the task remains queued or still has retries. A stale `created + pending` report is
therefore separately recoverable after the configured threshold.

## Creation-versus-delivery race

The authenticated handler accepts the current generation even while creation is still
marked `creating`. Delivery itself reconciles the state to `created`. The original task
creator uses an ownership-token conditional update and cannot overwrite the handler or a
newer generation.

## Handler authentication and acknowledgement

The internal API route is stateless and does not use browser sessions, cookies, CSRF,
citizen authentication, or the citizen rate limiter. Google signature, issuer, audience,
time claims, and approved service-account identity are verified before payload handling.
Cloud Tasks informational headers are never trusted as identity.

HTTP outcomes follow Cloud Tasks acknowledgement behavior:

- missing or invalid authentication: `401` or `403`;
- authenticated permanent malformed payload: safe `2xx` acknowledgement;
- completed, duplicate, or stale generation: safe `2xx` acknowledgement;
- permanent missing or tampered evidence after controlled state recording: safe `2xx`;
- live processing lease: retryable `409`;
- transient inference/infrastructure failure: retryable non-`2xx`.

The handler does not use `400` for authenticated permanent task failures because that
would cause pointless Cloud Tasks retries. It also avoids `429` and `503` for normal
lease conflicts.

## Timeout hierarchy

Configuration validation enforces:

```text
FastAPI total timeout
    < Cloud Tasks HTTP dispatch deadline
    < AI processing lease duration
```

The CreateTask client timeout is also bounded and must remain below the dispatch
deadline. The default Stage 1 values are 20, 45, and 60 seconds respectively, with a
10-second CreateTask timeout.

## Recovery command

The command defaults to a read-only audit:

```text
php artisan phase10a:recover-ai-dispatch
```

Execution requires both the `cloud_tasks` dispatcher and an explicit flag:

```text
php artisan phase10a:recover-ai-dispatch --execute
```

It covers:

- `not_started`, definite `failed`, `uncertain`, and expired `creating` dispatches using
  the same generation;
- stale `created + pending + no AI attempt` dispatches using a new generation.

It does not retry completed AI, live AI leases, public tracking requests, or public
submission replays.

## Phase 10B provisioning requirements

Before enabling `cloud_tasks`, Phase 10B must verify:

- Laravel task creator has narrowly scoped `roles/cloudtasks.enqueuer` on the intended
  queue;
- Laravel may act as only the configured Cloud Tasks OIDC service account;
- the OIDC service account belongs to the same project as the queue;
- Google's Cloud Tasks service agent retains its required service-agent role;
- application principals do not receive Owner, Editor, or Cloud Tasks Admin merely to
  enqueue tasks;
- queue-level URL, path, header, OIDC identity, and audience overrides cannot redirect or
  weaken the task contract;
- task-level and queue-level OIDC settings are not contradictory;
- retry limit, exponential backoff, maximum attempts, dispatch deadline, and retention
  are explicitly reviewed;
- real deployed Cloud Tasks tokens are inspected to confirm whether identity binding uses
  verified `email + email_verified` or the documented service-account `sub` value.

Downloaded service-account keys must never be added to source, images, mobile, FastAPI,
or repository artifacts.

## Stage 1 verification

Stage 1 uses isolated SQLite, fake Cloud Task creation, fake signed-claim verification,
and mocked FastAPI responses. Real Google Cloud APIs, IAM, queues, Cloud Run, and the live
PostgreSQL schema remain untouched until their separate approval gates.

## Stage 2 disposable PostgreSQL verification

The committed Phase 10A implementation was validated against the guarded disposable
Supabase PostgreSQL schema `civiclear_phase9a_test_20260811_phase10a_s2`.

- all 20 migrations completed over a verified TLS connection;
- 16 Phase 10A feature tests passed with 132 assertions;
- the disposable schema was removed and its absence was verified after testing;
- the protected live `civiclear` schema was not selected by the test connection.

The full Laravel suite had already passed sequentially during Stage 1. An earlier
failure caused by overlapping PHPUnit processes sharing fake-storage paths was not
treated as an application defect; the focused test and the complete sequential suite
both passed afterward.

## Stage 3 live additive migration

The immutable implementation commit is:

```text
cb2b709fb787947f8de936ebc2a869a1d83723e2
```

Before the live migration, Laravel entered maintenance mode and PostgreSQL produced a
verified custom-format backup outside the repository. Its SHA-256 is:

```text
de7a7ba44f0d878fc9171e46e837260c48c3973691c9ba7f63170fa79ebf8931
```

The additive migration then completed with the following verified invariants:

- migration rows increased from 19 to 20;
- all 29 existing reports were preserved;
- all 17 uploaded photograph records and all 17 Supabase Storage references were
  preserved;
- all ten Phase 10A task-state columns exist;
- existing reports retain generation `0`, zero task-creation attempts, and null task
  identifiers and errors;
- the active dispatcher remains `inline`;
- Laravel left maintenance mode successfully;
- the Git worktree remained clean.

## Post-migration manual verification

The user manually verified the local submission, tracking, staff-dashboard, private
photograph, and server-side AI flow after the migration. The protected internal task
endpoint returned HTTP 401 without Google identity, and the recovery command refused
to execute because `cloud_tasks` is not the active dispatcher.

These results verify backward compatibility and fail-closed behavior. They do not claim
that a real Google queue, IAM policy, deployed OIDC token, or Cloud Run delivery has been
tested. Those remain Phase 10B and Phase 10C gates.
