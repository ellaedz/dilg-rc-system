# Phase 10B — Azure Secure Deployment

## Status

Stage 0 inspection and Stage 1 local implementation are complete. The immutable
pre-build commit remains approval-gated. Work is on:

```text
feature/phase-10b-azure-secure-deployment
```

The approved starting commit is:

```text
eb66533a4f64bc93c2a507cf71ea8879dea04dd8
```

No Azure resource, Entra application, secret, container image, deployment, production
database row, or production Storage object was created or changed during Stage 1.

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

These are local and mocked validation results. They do not claim that images have been
built or that Azure, Entra, Supabase-from-Azure, or physical mobile behavior works.

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

## Remaining approval gates

1. Complete isolated tests and full diff/secret review.
2. Obtain explicit approval to create the immutable pre-build implementation commit.
3. Record the complete commit SHA and require a clean worktree.
4. Verify Azure for Students cost, quotas, providers, regional availability, ACR mode,
   ACR Tasks availability, Entra permissions, and custom-role permission without writes.
5. Obtain separate approval before creating Azure resources.
6. Obtain separate approval before creating Entra applications, app-role assignments,
   or the narrow custom queue role.
7. Obtain separate approval before setting the first Key Vault secret versions.
8. Obtain separate approval before remote builds.
9. Inspect image digests, SBOMs, vulnerabilities, and runtime contents before deployment.
10. Obtain separate approval before deployment and production traffic.
11. Prove all required managed-identity operations before separately approving Shared
    Key shutdown.

If the current user cannot create the narrow queue visibility role or assign the Entra
application roles, stop and report the exact permission gap. Do not self-assign broader
administrator rights, create client secrets, or grant Storage Queue Data Contributor as
a silent workaround.

## Known production blockers outside Stage 1

- Public tracking still carries the opaque Tracking Token in a GET path. It must move to
  a non-URL credential transport before production acceptance.
- MPDO barangay polygons are validated input for Phase 13A but are not yet integrated.
- Real Azure resources, real managed-identity operations, remote images, deployment,
  cloud latency, cold starts, and mobile end-to-end behavior remain unverified.
- Storage Shared Key remains enabled in the initial template solely as a controlled
  migration state and must not be disabled until every required managed-identity queue
  operation and scaler behavior are proven.

## Test isolation

Laravel tests remain forced to `APP_ENV=testing`, in-memory SQLite, array cache/session,
sync queue, and local/fake Storage. Azure network access is prevented by HTTP fakes and
fake queue/token providers. FastAPI authorization tests use controlled decoded claims;
they do not contact Microsoft Entra. No test may use the live Supabase `civiclear`
schema, the real Supabase photograph bucket, real Azure resources, or the local evidence
directory.
