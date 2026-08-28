# Phase 8F Stage B — Mobile Native AI Cleanup

## Scope

Branch: `chore/phase-8f-stage-b-remove-mobile-tflite`

Starting commit: `ef85c62b4f2dbd59553ab1d50adab23bdf30d77a`

Planned commit message: `chore: remove mobile TFLite runtime`

Stage A physically verified the Android-to-Laravel-to-FastAPI report path before this
cleanup began. Stage B removes only the now-unused phone inference implementation and
produces a fresh development APK for verification.

## Removed mobile-only runtime

- `react-native-fast-tflite` and `react-native-nitro-modules`;
- the TFLite Expo plugin and Metro asset extensions;
- the bundled Float16 and Float32 mobile models, labels, and metadata;
- the phone model loader, decoder, NMS, inference result card, and model preprocessing;
- model-specific draft fields and their legacy persistence; and
- tests that existed only for the deleted phone inference implementation.

Existing Stage A drafts remain loadable. Their obsolete citizen classification and
phone-inference keys are discarded when the draft is normalized.

## Preserved contracts

- camera and gallery selection;
- app-owned JPEG preparation, compression, and recovery snapshots;
- foreground GPS and Santa Cruz municipality validation;
- idempotent Laravel submission;
- SecureStore Tracking Tokens, history, polling, and terminal-state behavior;
- Laravel private-photo persistence and server-AI orchestration; and
- the FastAPI-owned Float16 model, labels, metadata, and approved SHA-256.

The FastAPI model SHA-256 remains:

```text
deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f
```

## Verification gates

```powershell
cd mobile
npm run typecheck
npm run lint
npm test
npx expo-doctor
npx expo export --platform android --clear
npm ls react-native-fast-tflite react-native-nitro-modules --all
```

A disposable native build from the final Stage B commit must additionally verify:

- no `.tflite` asset or TFLite/Nitro native library is packaged;
- `android:allowBackup="false"` remains present;
- `RECORD_AUDIO` remains absent;
- camera and foreground-location permissions remain;
- APK ABI, size, SHA-256, and signature; and
- physical camera, gallery, GPS, submission, server AI, history, and polling.

Post-commit APK measurements and the resulting Stage B commit SHA are reported in the
Codex handoff. They are not inserted afterward into this commit.

## Explicit exclusions

Stage B does not modify the Phase 8F-R recovery-discard defect, Tracking Token UX,
branding, GIS data, barangay status fields, Laravel/FastAPI contracts, database schema,
Supabase, Cloud Run, or final release signing.
