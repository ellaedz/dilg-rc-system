# Phase 8F Stage A — Mobile Server-AI Flow

## Scope

Branch: `feature/phase-8f-mobile-server-ai-flow`

Starting commit: `48f46c4b6d8d46812ee17e080e465fc1e33e4dbd`

Planned commit message: `feat: move mobile reporting to server AI`

Stage A changes only the managed Expo mobile source and project documentation. It does
not migrate the database, change Laravel schema, deploy cloud resources, modify `.env`,
or remove TFLite. Native AI removal remains a separate Stage B decision after physical
server-inference parity.

## Implemented contract

### Server-owned classification

The report form no longer initializes TFLite or requires an Analyze Photo action.
Multipart submission includes only the photograph, description, GPS coordinates,
accuracy, and timestamp. It deliberately omits:

```text
selected_violation_type
image_result
image_confidence
image_validation_status
image_model_version
needs_manual_review
```

The mobile UI describes FastAPI output as a “Possible Violation.” Authorized staff
verification remains independent and authoritative.

### Durable idempotent submission

Every draft has a random local identifier. Before network transmission, the app creates
an immutable app-owned evidence snapshot and a stable high-entropy Idempotency-Key.
Explicit retries reuse both.

Approved states:

```text
draft
prepared
submitting
uncertain
submitted
failed_retryable
failed_permanent
```

An interrupted `submitting` state becomes `uncertain` on startup without an automatic
upload. The citizen must explicitly retry or discard it. A module-level in-flight gate
ensures concurrent taps and lifecycle re-entry share one submission operation.

Recovery is limited to ten unresolved snapshots and 10 MB per photograph. Snapshot
ownership is validated against the app document directory. Uncertain snapshots are not
silently deleted.

### Identifier separation and secure storage

The mobile types and navigation distinguish:

- Report Number;
- opaque Tracking Token;
- local record ID; and
- Idempotency-Key.

Raw tokens are written only to `expo-secure-store` with this-device-only accessibility.
AsyncStorage retains safe metadata. Success, history, and tracking navigation pass only
the local record ID. Tokens remain case-sensitive, are masked by default, and require
explicit warning-backed reveal or copy.

Legacy history is migrated copy-on-write. Recoverable opaque tokens move to SecureStore
before the old AsyncStorage entry is removed. Sequential-only identifiers remain legacy
staff references and are never treated as anonymous tracking credentials.

The status parser trusts `report_number` for display and ignores the transitional
`tracking_id` alias, so a status response cannot overwrite the retained raw credential.

### Polling and state display

Polling uses bounded delays of 5, 10, 20, and 30 seconds, cancels screen updates when the
screen unmounts, and stops for `Resolved`, `Closed`, or `Rejected`. Report, AI,
verification, and barangay-routing states are displayed independently.

### Runtime configuration and Android privacy

Development accepts a correctly formed LAN `/api` URL. Production rejects HTTP,
localhost, and private-network API hosts and requires public HTTPS.

The About screen includes a development-only, redacted API diagnostic. Android backup
is disabled in Expo configuration. The SecureStore plugin does not add separate backup
rules because the application itself is non-backupable.

The production contract now keeps the bearer-equivalent token out of the URL. The
mobile client calls `GET /api/mobile/reports/status` and supplies the exact token through
the standard `Authorization: Bearer` header. Production proxies, APM, and application
logs must continue to redact authorization headers.

## Automated verification

The Stage A mobile tests cover:

- absence of trusted AI/classification request fields;
- exact case-sensitive opaque token validation;
- production HTTPS enforcement;
- stable high-entropy idempotency generation;
- approved recovery state transitions;
- same-key immutable recovery replay;
- startup conversion of interrupted submission to uncertain;
- explicit recovery deletion;
- concurrent double-tap deduplication;
- polling delay and terminal-state behavior; and
- the existing TFLite decoder/NMS regression evidence retained for Stage B.

Required commands:

```powershell
cd mobile
npm run typecheck
npm run lint
npm test
npx expo-doctor
```

Laravel and FastAPI regression suites must remain green because Stage A consumes their
existing contracts.

The July 29, 2026 production-dependency audit reports 15 moderate, 21 high, and zero
critical transitive advisories in the pinned Expo SDK 54 / React Native toolchain. The
suggested aggregate remedies jump to Expo 57 or React Native 0.86 and are outside this
phase's tested compatibility contract. Do not run `npm audit fix --force`; handle the
framework upgrade as a separately reviewed dependency phase before production release.
Expo Doctor remains the authoritative SDK-consistency gate for Stage A.

## Disposable native verification

Generated `mobile/android` is ignored and must not be edited or committed. After the
Stage A source commit, archive that exact commit into a temporary directory, run
`npm ci`, generate Android, and verify:

```text
android:allowBackup="false"
```

Use Android Studio’s bundled JDK and the installed Android SDK for any local Gradle
build. Report the result truthfully if environmental downloads prevent completion.

## Manual physical-device exit gate

- Rebuild/install the Stage A development client.
- Confirm camera and gallery prepare app-owned photographs.
- Capture and validate a real Santa Cruz GPS point.
- Submit without tapping or running local AI.
- Confirm one report for a rapid double tap.
- Confirm Laravel receives no trusted mobile AI/category fields.
- Simulate an interrupted response and explicitly retry the uncertain request.
- Confirm replay returns the same Report Number and Tracking Token.
- Confirm the token is masked, can be warning-revealed/copied, and survives restart.
- Confirm manual exact-token entry, history, status polling, and terminal stop.
- Confirm AI and barangay states remain advisory/independent.

Do not merge Stage A or begin Stage B until the user completes this physical-device
verification.
