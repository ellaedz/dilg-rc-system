# Phase 10B — Azure Secure Deployment

## Status

The approved Azure infrastructure and workloads are deployed. Immutable images,
version-pinned secrets, managed-identity service calls, Supabase connectivity, and the
complete Azure Queue lifecycle have been verified. Work remains on:

```text
feature/phase-10b-azure-secure-deployment
```

The approved starting commit is:

```text
eb66533a4f64bc93c2a507cf71ea8879dea04dd8
```

Stage 1 remained isolated and did not mutate cloud or production data. Later approved
stages created and verified the Azure resources without using a production report as a
test fixture.

## Stage 1 validation evidence

- Complete Laravel suite: 161 tests passed, 1,081 assertions.
- Complete FastAPI suite: 32 tests passed.
- Phase 10B Azure unit contract: 11 tests passed, 48 assertions.
- All four Bicep templates compiled with no diagnostics using Microsoft Bicep CLI
  0.45.15 after verifying the downloaded executable against its GitHub release digest.
- Compose and GitHub Actions YAML parsed successfully.
- Composer configuration and lock file validated; Composer audit reported no known
  security advisories.
- All four pinned official Docker base-image digests were found in Docker Hub.
- The filename/content safety scan reviewed 50 changed or new files and found no
  prohibited filename, credential pattern, private key, database dump, GIS package, or
  tracked `.env` file.
- `git diff --check` passed.

These results describe the isolated Stage 1 gate. The live evidence below separately
records what was subsequently proven in Azure; physical mobile end-to-end verification
remains outside this phase.

## Live Azure verification evidence

- Immutable Laravel and FastAPI images were built from reviewed commits, pushed by
  digest, and inspected with SPDX SBOM and SLSA provenance attestations.
- Laravel and FastAPI Container Apps and the event-driven worker job provisioned and
  ran successfully with user-assigned managed identities. Laravel returned HTTP 200 on
  its public health endpoint; FastAPI remained internal-only.
- Entra-protected Laravel-to-FastAPI and worker-to-Laravel calls acquired tokens with
  the expected tenant, audience, application role, client ID, and principal ID.
- A controlled marked test report completed through Supabase, Azure Queue, the worker,
  internal FastAPI, and Laravel persistence. Supabase S3 access was also verified with
  a create/read/delete canary before the superseded key was revoked.
- Azure Queue send, receive, visibility update, delete, quarantine, and KEDA scaler
  metadata access were proven with narrowly scoped managed identities.
- Storage Shared Key was then disabled on `stcivicleara41a250a`. The complete queue
  behavior was retested: a valid message was acknowledged, a leased task logged
  `retry_scheduled` and was later acknowledged, and an invalid envelope logged
  `quarantined`. The exact quarantine canary was verified and deleted, the quarantine
  queue was empty afterward, and the temporary human queue role was removed.

## Approved architecture

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

The validated MPDO barangay dataset remains Phase 13A work. Phase 10B does not alter
barangay assignment, mobile UI, staff verification, AI classification semantics, or
official violation fields.

## Stage 1 implementation

- Laravel retains the Phase 10A report-first, photograph-first, deterministic task
  generation, creation lease, processing lease, retry, and duplicate-delivery contract.
- The new provider adapter sends the provider-neutral task payload through Azure Queue
  Storage REST using a Microsoft Entra bearer token requested for
  `https://storage.azure.com/`.
- Managed-identity token selection requires a trusted user-assigned identity client ID
  and a fixed server-configured resource. Citizen input, report data, and queue messages
  cannot choose either value.
- The worker retrieves one message, invokes the protected Laravel task endpoint,
  deletes acknowledged work, updates message visibility for controlled retry, and moves
  exhausted or permanent failures to a separate quarantine queue.
- The Container Apps Job platform has no competing retry loop: retry limit is zero,
  maximum executions and parallelism are one, and Queue visibility is authoritative.
- Entra authorization binds tenant, audience, Applications-only role, application client
  ID, and exact managed-identity principal object ID.
- FastAPI keeps `/health` and `/ready` available for platform probes while protecting
  versioned inference endpoints when `FASTAPI_AUTH_MODE=azure_entra`.
- Laravel and FastAPI images run as non-root users. Entrypoints do not migrate databases,
  generate keys, populate secrets, or enable writes.

## Azure resource constraints encoded locally

- Workload Profiles v2 Container Apps environment using only the built-in Consumption
  profile.
- Laravel scale: zero to two replicas.
- Internal FastAPI scale: zero to one replica, one Uvicorn worker, initial concurrency
  one.
- AI job: zero to one execution, parallelism one, platform retry limit zero.
- Basic ACR with admin credentials disabled.
- Three user-assigned managed identities.
- Queue RBAC at individual queue scope:
  - Laravel: Storage Queue Data Message Sender on the primary queue;
  - worker: Storage Queue Data Message Processor plus a narrow custom
    `messages/write` role on the primary queue;
  - worker: Storage Queue Data Message Sender on the quarantine queue.
- Runtime identities receive pull-only ACR access. Laravel and worker receive Key Vault
  Secrets User at vault scope for later version-pinned references.
- Logging retention is bounded to 30 days.
- GPU, Dedicated profiles, Flexible profiles, premium registry, and always-running
  replicas are absent.

## Build and secret controls

The approved remote-build fallback is a manually dispatched GitHub Actions workflow,
not ACR Tasks. It requires a complete reviewed commit SHA, checks out that exact commit,
requires a clean worktree, scans tracked filenames and credential patterns, preserves a
filename-only source manifest, and creates Git-SHA-tagged images with provenance and
SBOM metadata.

`.dockerignore` excludes environment files, credentials, certificates, dumps, SQLite
files, private evidence, logs, caches, virtual environments, GIS packages, local mobile
artifacts, tests, and Git metadata. Secrets must never be passed as build arguments.

The Laravel and worker runtime image includes one narrowly allow-listed public trust
anchor: the Supabase Root 2021 CA. Its verified SHA-256 is
`700723581420dd1ac98fd7e9ac529f0ef210eadcaf87fc868a3ad7d114c2f3b7`. The image sets
`DB_SSLROOTCERT` to that read-only Linux path so PostgreSQL `verify-full` can validate
Supabase TLS in Azure. No client certificate, private key, database credential, or other
secret is packaged in the image. The remote-build filename gate exempts only that exact
public CA path from its certificate prohibition; every other tracked `.crt` remains
prohibited. Its other exact exceptions are the three tracked `.env.example` templates
and the private-storage `.gitignore` placeholder, none of which contains a credential.

## Remaining production acceptance gates

1. Deploy and verify the locally implemented non-URL `Authorization: Bearer` Tracking
   Token contract before production acceptance.
2. Record measured latency, cold starts, processing duration, Queue visibility, worker
   timeout behavior, replica limits, logs, and student budget alerts.
3. Complete Phase 10C physical mobile end-to-end verification before production traffic.

If the current user cannot create the narrow queue visibility role or assign the Entra
application roles, stop and report the exact permission gap. Do not self-assign broader
administrator rights, create client secrets, or grant Storage Queue Data Contributor as
a silent workaround.

## Known production blockers outside Stage 1

- Public tracking has been changed locally to carry the opaque Tracking Token only in
  the Authorization header. A new immutable Laravel image must be deployed and the
  contract verified in Azure before production acceptance.
- MPDO barangay polygons are validated input for Phase 13A but are not yet integrated.
- Cloud latency, cold starts, budget alerts, and physical mobile end-to-end behavior
  remain unverified.
- Storage Shared Key is disabled and the required managed-identity queue operations and
  scaler behavior have been proven after shutdown.

## Test isolation

Laravel tests remain forced to `APP_ENV=testing`, in-memory SQLite, array cache/session,
sync queue, and local/fake Storage. Azure network access is prevented by HTTP fakes and
fake queue/token providers. FastAPI authorization tests use controlled decoded claims;
they do not contact Microsoft Entra. No test may use the live Supabase `civiclear`
schema, the real Supabase photograph bucket, real Azure resources, or the local evidence
directory.
