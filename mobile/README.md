# CIVICLEAR Citizen Mobile App

Android-first Expo application for anonymous road-clearing reports in Santa Cruz,
Laguna.

## Current architecture — Phase 8F Stage B

The phone captures and prepares evidence. Laravel owns durable report creation,
idempotency, private-photo storage, GIS routing, and orchestration of FastAPI server
inference. Citizens do not choose or submit a trusted violation category.

The current submission sends only:

- one app-owned JPEG evidence snapshot;
- optional written description;
- incident latitude and longitude;
- GPS accuracy;
- incident timestamp; and
- one stable high-entropy `Idempotency-Key` for the logical draft.

It does not send `selected_violation_type`, `image_result`, `image_confidence`,
`image_validation_status`, `image_model_version`, or `needs_manual_review`.

The Android client no longer includes a TensorFlow Lite or Nitro runtime, model asset,
model loader, decoder, NMS implementation, or model-specific draft state. The approved
Float16 model, labels, metadata, and hash remain owned by the FastAPI service. General
photo compression and app-owned evidence preparation remain on the phone.

## Submission recovery

Selecting a photograph creates an app-owned processed copy under the Expo document
directory. At submission time the app creates an immutable recovery snapshot containing
the evidence and canonical request fields.

The durable state machine is:

```text
draft
  -> prepared
  -> submitting
  -> submitted
  -> uncertain
  -> failed_retryable
  -> failed_permanent
```

Only approved transitions are accepted. A request interrupted in `submitting` becomes
`uncertain` at the next startup. It is displayed to the citizen but never automatically
resent. An explicit retry reuses the original snapshot and Idempotency-Key. Concurrent
submission calls for the same draft share one in-flight operation.

At most ten unresolved recovery snapshots are retained. A photograph may not exceed
10 MB. Uncertain evidence is never silently deleted; the citizen must retry or
explicitly discard it.

## Identifier and credential boundaries

The app keeps four meanings separate:

- `reportNumber`: public staff reference such as `RCV-2026-0001`;
- `trackingToken`: case-sensitive 43-character anonymous lookup credential;
- `localRecordId`: random on-device navigation and history identifier;
- `idempotencyKey`: stable non-secret retry identity for one logical submission.

Raw Tracking Tokens are stored only with `expo-secure-store`. AsyncStorage contains
safe report metadata and never stores new raw tokens. Success/history navigation uses
only `localRecordId`. The token is masked by default and copy/reveal actions show a
privacy warning.

Legacy AsyncStorage entries are migrated copy-on-write:

- an opaque token is copied to SecureStore before safe metadata replaces it;
- a sequential-only `RCV-...` entry remains staff-reference history;
- a sequential identifier is never reinterpreted as an anonymous credential.

Status responses are validated against the Report Number field. The transitional
`tracking_id` response alias is never allowed to replace the locally retained token.

## Tracking and polling

Public lookup uses:

```text
GET /api/mobile/reports/status/{tracking_token}
```

Polling starts only while a relevant screen is mounted. It uses bounded backoff
(`5s`, `10s`, `20s`, `30s`), stops at `Resolved`, `Closed`, or `Rejected`, and cancels
screen updates when the screen unmounts.

Report status, AI-processing status, verification status, and barangay-routing status
are displayed independently. Citizen-facing AI language says “Possible Violation” and
never “Confirmed Violation.”

Because the current Laravel contract places the bearer-equivalent token in the URL
path, production deployment must redact `/api/mobile/reports/status/*` paths from
proxy, CDN, Laravel, APM, and access logs before public release. Moving the credential
to a non-logged authorization channel requires a separately versioned backend contract.

## API configuration

Create `mobile/.env` from `.env.example`.

Local physical-device testing may use the computer LAN address:

```text
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.100:8000/api
```

Production builds reject HTTP, localhost, and private-network API URLs. Production must
use a public HTTPS `/api` endpoint. The About screen exposes a development-only,
redacted reachability diagnostic.

## Privacy and Android security

- anonymous reporting requires no citizen account;
- camera/gallery/location permissions are requested only from explicit user actions;
- background location, audio recording, video, and continuous camera analysis are not
  used;
- Android application backup is disabled;
- private tokens are not placed in navigation parameters or AsyncStorage;
- generated Android/iOS folders remain ignored and must not be edited directly.

## Local development

```powershell
cd mobile
npm ci
npm run typecheck
npm run lint
npm test
npx expo-doctor
```

For local Android verification, use Android Studio’s bundled JDK and SDK when they are
not already configured:

```powershell
$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
$env:Path = "$env:JAVA_HOME\bin;$env:ANDROID_HOME\platform-tools;$env:Path"
```

Generated native verification must be performed from a disposable archive of the
committed source. Verify the resulting manifest contains:

```xml
android:allowBackup="false"
```

Do not commit generated `android/`, build outputs, `.env`, tokens, snapshots, or test
databases.

The current npm advisory feed reports transitive high/moderate issues whose aggregate
fix requires an unapproved Expo/React Native major upgrade. Do not use
`npm audit fix --force`; production release requires a separately tested framework
upgrade. No critical advisory is currently reported.

## Stage B acceptance

- Camera and gallery both prepare evidence on a representative Android phone.
- GPS validates a Santa Cruz incident point.
- Submission succeeds without a local Analyze Photo step.
- Laravel receives no trusted mobile classification/AI fields.
- A double tap creates one report.
- Network-response loss produces an explicit uncertain recovery item.
- Retry returns the same Report Number and Tracking Token.
- Token reveal/copy, secure history, manual entry, tracking, and polling work.
- Server AI output is shown only as a possible violation.
- The generated APK contains no mobile TFLite model or TFLite/Nitro native runtime.

The physical-device `Discard Local Recovery` defect is deferred to the separately
scoped Phase 8F-R follow-up. Stage B does not modify that workflow.
