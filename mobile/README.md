# DILG-RC Citizen Mobile App

Phase 7 defense stabilization verifies TypeScript, ESLint, Jest, Expo Doctor, Android
Hermes export, model bundling, and EAS archive composition. Physical camera/gallery,
TFLite execution, GPS, submission, and tracking refresh must still be recorded on the
representative Android phone. See `../PHASE_7_DEFENSE_VALIDATION.md`.

Native Android-first Expo application for the DILG-RC Road Clearing Violation Reporting System.

Phase 5D + Phase 5E source implementation completes the citizen workflow on top of the
Phase 5C TensorFlow Lite image analysis: photo evidence, one-tap AI analysis, written
description, foreground GPS capture, Santa Cruz municipality validation, Laravel
multipart submission, Tracking ID storage, public status tracking, and local history.
Physical Android acceptance is still pending, so this must not yet be treated as
release-complete.

Phase 6A-6D adds Laravel-mediated NLP/GIS/fusion processing after report storage. The
mobile app still performs image inference locally and sends only the image result,
confidence, description, and GPS to Laravel. Submission success and public tracking may
show AI processing status, final advisory category, and a manual-review notice. Raw
FastAPI responses, stack traces, and internal notes are never exposed to citizens.

This project is pinned to Expo SDK 54 and React Native 0.81.5. TensorFlow Lite inference
requires the DILG-RC custom development build and does not run in standard Expo Go.

## Architecture

- `app/` contains Expo Router screens and tab navigation.
- `components/` contains reusable native UI pieces.
- `context/TrackingContext.tsx` owns local Tracking ID and status-history storage.
- `context/ReportDraftContext.tsx` owns unfinished citizen report draft, GPS, municipality, and AI state.
- `services/ImageInferenceService.ts` owns model initialization and one-photo inference.
- `services/api.ts` centralizes Laravel municipality validation, submission, and status tracking.
- `constants/inference.ts` owns model identity, tensor sizes, and thresholds.
- `utils/imageProcessing.ts` prepares report photos and creates the 640x640 model tensor.
- `utils/yoloDecoder.ts` decodes channel-major YOLO output.
- `utils/nonMaximumSuppression.ts` implements per-class NMS.
- `utils/validators.ts` contains Tracking ID and report draft validation.
- `types/` contains TypeScript contracts for drafts, submissions, status responses, and API errors.

## Phase 5C.0 Model Artifacts

`assets/models/` contains:

- `best_float16.tflite` - preferred 21.39 MiB mobile artifact.
- `best_float32.tflite` - 42.69 MiB compatibility/reference artifact.
- `labels.txt` - exact five-class training order.
- `model_metadata.json` - versions, hashes, tensor contract, and verification results.

Both TFLite files load and allocate successfully with TensorFlow Lite 2.19.0. Input is
Float32 NHWC `[1, 640, 640, 3]`; raw output is Float32 `[1, 9, 8400]`. Phase 5C uses
`best_float16.tflite`. The Float32 artifact remains a compatibility/reference model.

## Phase 5C Inference Architecture

- Runtime: `react-native-fast-tflite` 3.0.1 with `react-native-nitro-modules`.
- Execution delegate: CPU first for correctness and device compatibility.
- Model lifecycle: loaded once when the report workflow opens and cached for reuse.
- Trigger: only when the citizen taps `Analyze Photo`; no live-frame or background loop.
- Input: selected Phase 5B JPEG, orientation-corrected, aspect-ratio resized, and
  letterboxed to 640x640 with RGB `[114, 114, 114]` padding.
- Tensor: NHWC Float32 RGB normalized with `pixel / 255.0`.
- Output: channel-major `[1, 9, 8400]`, with 4 box channels and 5 class-score channels.
- Postprocessing: confidence filtering at `0.40`, per-class NMS at IoU `0.45`, and a
  maximum of 20 retained detections.
- Accepted result: confidence `>= 0.60`.
- Manual review: confidence `0.40-0.59` or no supported detection.
- Performance fields: initialization, preprocessing, inference, decoding, and total time.

TensorFlow Lite inference is triggered per selected photo. It does not continuously
process camera frames.

Exact class order:

```text
0 construction_materials
1 garbage_debris
2 illegal_parking
3 road_obstruction
4 sidewalk_obstruction
```

The model covers five visible-condition classes while the wider DILG taxonomy contains
additional categories. A no-detection result does not prove that no violation exists.
All AI results remain suggestions until authorized staff verifies or corrects them.

## Installed Phase 5C Packages

- `expo-dev-client` - custom development launcher for native modules.
- `react-native-fast-tflite` - local TFLite model loading and raw tensor execution.
- `react-native-nitro-modules` - native JSI/Nitro bridge used by the TFLite runtime.
- `expo-asset` - bundled labels asset resolution.
- `jpeg-js` - static JPEG decoding into RGBA bytes for RGB Float32 conversion.
- `jest-expo` - unit-test environment for the Expo SDK 54 project.
- `eslint` and `eslint-config-expo` - mobile lint gate.

## Phase 5B Features

- Camera photo capture through Expo-supported native image picker flow.
- Gallery image selection for image-only evidence.
- Required photo evidence with preview, replacement, removal, source badge, and metadata.
- Citizens are not asked to classify the violation; Phase 5C produces an AI suggestion for staff verification.
- Optional multiline context with a 10-character minimum when provided and a 500-character maximum.
- Automatic timestamp displayed in `Asia/Manila`.
- Local draft persistence using AsyncStorage.
- Restore/discard dialog when an unfinished draft exists.
- Improved Home, Submit, Track, History, About, Privacy, and bottom navigation UI.

## Installed Phase 5B Packages

- `expo-camera` - camera permission config and Expo-compatible camera capability.
- `expo-image-picker` - camera launch, gallery selection, and image-only picker permissions.
- `expo-image-manipulator` - orientation-safe image processing and JPEG compression.
- `expo-file-system` - processed image file metadata such as local file size.
- `expo-location` - foreground GPS capture for Phase 5D incident location.
- `expo-clipboard` - Tracking ID copy action on successful submission.
- `@react-native-async-storage/async-storage` - local Tracking ID and report draft storage.

All packages were installed with `npx expo install` for Expo SDK 54 compatibility.

## Camera Setup

Camera capture starts only after the citizen taps `Take Photo`.

Behavior:

- Requests camera permission at tap time.
- Opens native camera for one image.
- Does not support video capture in this phase.
- Does not run continuous camera inference; analysis occurs only after `Analyze Photo`.
- Does not upload the image.
- Keeps gallery selection available if camera permission is denied.

Android permission message:

`DILG-RC uses the camera only when you choose to capture road-clearing photo evidence.`

## Gallery Setup

Gallery selection starts only after the citizen taps `Choose from Gallery`.

Behavior:

- Requests photo library permission only when needed.
- Allows image selection only.
- Does not allow video.
- Preserves the previous selected image if the citizen cancels.
- Does not modify the original gallery image.

Android/photo permission message:

`DILG-RC accesses selected photos only when you choose an image as road-clearing evidence.`

## Image Processing Settings

Selected images are processed before being saved to the local draft:

- Maximum long edge: `1600px`
- JPEG quality: `0.82`
- Output format: JPEG
- Stored metadata: processed URI, width, height, approximate file size, and source (`camera` or `gallery`)

The processed copy is used for Phase 5C inference and future upload. The citizen's
original gallery image is not permanently modified. A temporary 640x640 inference copy
is decoded into RGB, normalized to Float32, and released after each analysis.

## Report Draft Persistence

Drafts are stored locally with AsyncStorage under the report draft context.

Stored:

- description
- processed image URI
- image source
- image width, height, and file size
- timestamp
- AI class suggestion and confidence
- inference time and validation status
- all post-NMS detections
- model version and SHA-256 identity

Not stored:

- citizen name
- email
- home address
- fake GPS values
- fabricated AI predictions

Future location fields remain `null`:

- latitude
- longitude
- gpsAccuracy
- detectedBarangay

When the app finds an unfinished draft, it asks the citizen to continue or discard it.

## Form Validation Rules

Photo:

- required
- must use a valid local image URI
- must be JPG, JPEG, PNG, or WEBP
- processed image must still exist locally

Description:

- optional
- whitespace-only text is treated as empty
- minimum 10 trimmed characters when provided
- maximum 500 characters

Timestamp:

- required
- valid ISO timestamp
- displayed as Asia/Manila

GPS is intentionally not validated yet. The citizen must complete a real image-analysis
attempt before continuing; error results require retry, while low-confidence and
no-detection results may continue as manual-review cases.

## Classification Ownership

The citizen supplies evidence and optional context, not an official violation category.

Classification flow:

1. Phase 5C AI analyzes the image and suggests a category with a confidence score.
2. A policy-mapping layer associates the visible condition with the approved DILG taxonomy.
3. Authorized staff verifies or corrects the suggestion before it becomes the official classification.

The AI suggestion must remain separate from the staff-verified classification. Low-confidence or unclear evidence must enter manual review rather than receiving a fabricated category.

The current Laravel `POST /api/mobile/reports` endpoint still requires the legacy
`selected_violation_type` request field. That boundary must be refactored in Phase 5D
before mobile submission is enabled. Phase 5C sends no Laravel request and never uses
an AI suggestion or citizen guess as a hidden official classification.

## Anonymous Reporting

Citizens do not register or log in. The app does not ask for a name, email, or home address.

Photo evidence, AI results, GPS coordinates, and municipality validation are sent only
when the citizen presses Submit Report. The app does not submit a fake barangay or NLP
result. If barangay polygons are unavailable, the mobile UI shows that the location is
inside Santa Cruz and that barangay assignment will be handled by DILG.

## Phase 5D + 5E Submission and Tracking

- Foreground GPS only through `expo-location`.
- GPS accuracy is shown as Excellent (`<=30m`), Acceptable (`31-80m`), or warning (`>80m`).
- `POST /api/gis/detect-barangay` validates inside/outside Santa Cruz and respects the
  backend barangay-unavailable response.
- `POST /api/mobile/reports` sends multipart form data with `photo`, `description`,
  `selected_violation_type`, GPS fields, and AI image-result metadata.
- Successful submission displays `Report Submitted Successfully`, the returned
  `RCV-YYYY-NNNN` Tracking ID, Copy, Track Report, and Back Home actions.
- Tracking IDs are stored locally with submission date, violation type, current status,
  verification status, municipality, assigned barangay, latest action, and last sync.
- `GET /api/mobile/reports/status/{tracking_id}` powers Track Report and Report History.
- Offline, timeout, validation, duplicate, upload, and server errors keep the draft
  locally for manual retry. No repeated auto-upload loop is implemented.
- Public tracking responses hide staff names, emails, internal remarks, and audit logs.

## API Base URL

Create `mobile/.env` from `mobile/.env.example`:

```bash
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.100:8000/api
```

Do not use `127.0.0.1` for a physical Android phone. Use the computer's LAN IP address.

Laravel local network command:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

The phone and computer must be on the same Wi-Fi. Windows Firewall may need to allow the PHP development server. Do not expose this local server publicly.

## Custom Development Build

TensorFlow Lite inference requires a custom Expo development build and will not run in
standard Expo Go. `eas.json` contains an Android development-client/internal-distribution
profile. The Android application ID is `com.dilgrc.citizen`.

Install dependencies and validate generated native configuration:

```bash
cd mobile
npm install
npx expo prebuild --clean --platform android
```

Create an installable development APK with EAS cloud build:

```bash
eas build --profile development --platform android
```

EAS is the preferred route when Android Studio/local Android build tools are unavailable.
Download the resulting APK from the EAS build page and install it on the Android phone.

For a local build, Java 17 and a configured Android SDK are required:

```bash
npx expo run:android --device
```

The repository remains managed Expo/CNG: generated `android/` and `ios/` directories are
ignored and should be regenerated instead of manually maintained.

## Running the Development Client

After the custom APK is installed:

```bash
cd mobile
npm run start:lan:clear
```

The LAN launcher targets the custom development client, detects the active Wi-Fi IPv4
address, and starts Metro on port `8081`. Open the printed connectivity-test URL on the
phone first. If it cannot display `packager-status:running`, Windows Firewall or Wi-Fi
client isolation is blocking the phone-to-laptop connection.

After the first successful connection, use `npm run start:lan`. Use the `:clear` variant
after native/configuration changes or stale bundles.

### Tunnel fallback

Run `npm run start:tunnel:clear` when LAN isolation blocks Metro. The tunnel targets the
custom development client and temporarily transfers the JavaScript bundle and assets
through Expo/ngrok. Stop it with `Ctrl+C`.

### USB fallback

USB testing does not require mobile data or a hotspot:

1. Enable Android Developer options and USB debugging.
2. Connect a data-capable USB cable and authorize the computer.
3. Run `npm run start:usb:clear`.
4. Open the DILG-RC development client and connect to the Metro server shown by Expo.

The custom development APK must already be installed; Expo Go is not a substitute.

## Physical Android Acceptance

Phase 5C cannot close until the Infinix Hot 40i or another representative Android phone
passes all of these checks:

- custom development APK installs and opens;
- Float16 model and five labels load;
- camera and gallery images both analyze;
- all five representative violation classes are exercised;
- unrelated, blurry, dark, and large images do not crash the app;
- low confidence and no detection enter manual review;
- replacing a photo clears stale AI data;
- repeated inference and app restart remain stable;
- initialization, preprocessing, inference, decoding, and total time are recorded;
- no Laravel or FastAPI request is sent.

## Current Limitations

- Physical-device TensorFlow Lite execution and latency are not yet verified.
- Physical-device GPS capture, Laravel upload, status refresh, and report-history sync
  still need custom APK testing on the Infinix Hot 40i or another representative Android phone.
- The local Gradle build requires Java 17; the Gradle distribution download did not
  complete within the automated build window.
- Expo Doctor reports `react-native-fast-tflite` as untested in React Native Directory's
  New Architecture metadata. Expo prebuild and autolinking succeed, but only a compiled
  custom build and device run can close this warning.
- No representative road-clearing test images are stored in the repository.
- Bounding boxes are retained in the draft but are not rendered over the preview yet.
- The model recognizes five classes, not every category in the wider DILG taxonomy.
- FastAPI/NLP now runs behind Laravel as the Phase 6A-6D local service. Notifications,
  citizen accounts, push messaging, cloud hosting, and release builds remain later work.
- Ultralytics AGPL-3.0 licensing metadata requires review before distribution.

## Upcoming Phases

- Complete physical Android acceptance for Phase 5C + Phase 5D/5E.
- Validate camera/gallery AI inference, GPS, municipality validation, Laravel upload,
  returned Tracking ID, dashboard visibility, status update, mobile tracking refresh,
  and local history on device.
- Validate the completed Phase 6A-6D local Laravel-to-FastAPI flow on the physical device.

Phase 6A-6D proceeded by explicit user authorization; the physical Android gate still
blocks deployment and release readiness.

## Quality Checks

```bash
npm install
npx expo-doctor
npm run typecheck
npm run lint
npm test
npx expo export --platform android --output-dir .expo-export-check --clear
```

Laravel regression commands from the repository root:

```bash
php artisan test
npm run build
composer validate
composer audit
npm audit
```

The Phase 5C test suite uses synthetic tensors for output layout, all five class mappings,
confidence thresholds, coordinate restoration, IoU, per-class NMS, and stale-draft reset.
Synthetic tests do not replace representative physical-device image testing.

Latest verification on July 15, 2026:

- 3 mobile test suites and 11 tests passed.
- TypeScript and ESLint completed with no errors or warnings.
- Phase 5D + 5E source checks passed: mobile TypeScript, mobile ESLint, mobile Jest,
  Laravel test suite, local migration, and root Vite production build.
- Expo Doctor passed 17 of 18 checks. The remaining item is the React Native Directory
  metadata warning for `react-native-fast-tflite` on the New Architecture; it is not hidden
  or excluded.
- Expo Android/Hermes export completed across 1,090 modules and bundled the 22,426,678-byte
  Float16 model plus `labels.txt`.
- The bundled model SHA-256 remained
  `deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f`.
- Android prebuild completed and explicitly removes `android.permission.RECORD_AUDIO`.
- The existing Laravel regression suite passed 17 tests and 94 assertions; the Vite
  production build and Composer validation also passed.
- Composer audit and the root npm audit found zero known vulnerabilities.
- Mobile npm audit reports 19 moderate transitive Expo SDK 54 advisories and zero high or
  critical vulnerabilities. `npm audit fix --force` is intentionally not used because it
  would install Expo 57 and break the supported SDK 54 client contract.
