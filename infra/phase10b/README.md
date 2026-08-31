# Phase 10B Azure infrastructure artifacts

These templates began as local Stage 1 artifacts and have since been deployed and
validated against the approved Azure for Students subscription. Runtime workloads use
immutable ACR image digests and version-pinned Key Vault references.

- `main.bicep` defines Basic ACR, Queue Storage, two queues, Key Vault, three
  user-assigned identities, bounded Log Analytics, and a Workload Profiles v2 Container
  Apps environment with only the built-in Consumption profile.
- `queue-visibility-role.bicep` defines the narrow message `write` permission required
  by Update Message. Deploying a custom role requires a separate permission check and
  approval.
- `rbac.bicep` assigns sender/processor/visibility permissions at individual queue scope
  and ACR pull at registry scope. It grants Key Vault Secrets User only to the Laravel
  and worker identities that consume version-pinned secret references. Do not replace
  the queue roles with Storage Queue Data Contributor for convenience.
- `workloads.bicep` defines public Laravel, internal FastAPI, and a one-at-a-time
  event-driven worker job. Image parameters must be immutable ACR digests.
- `entra-app-roles.example.json` records the Applications-only service roles. It is not
  an executable credential or tenant manifest.

Secret values are deliberately absent. `laravelSecretReferences` and
`workerSecretReferences` must contain reviewed, version-pinned Key Vault references only
after the separate secret-population approval. Never use `latest`, inline secret values,
or command-line secret payloads.

Storage Shared Key is disabled. Managed-identity queue send, receive, visibility update,
delete, quarantine, and scaler metadata access were retested successfully after the
shutdown; connection-string fallback is not part of the deployed workload contract.

ACR Tasks are not assumed. The approved remote-build fallback is the manually dispatched
GitHub Actions workflow, which checks out an exact full commit SHA, builds on the hosted
runner, produces a filename-only reviewed-source manifest, and pushes Git-SHA-tagged
images. Deployment still uses reviewed image digests.
