# Phase 8C — FastAPI Server-Side AI Inference

## Baseline and scope

- Starting branch: `main`
- Complete starting commit: `963385d6ee274faf93e0bb7730bdfeec1836c0ac`
- Implementation branch: `feature/phase-8c-server-side-ai-inference`
- Planned commit: `feat: add fastapi server-side image inference`
- Scope: local FastAPI image inference, response/readiness contracts, tests, and
  documentation
- Excluded: Cloud Run deployment, Supabase, Laravel orchestration, photo-storage
  changes, and removal of Android TFLite code

The Phase 8A backup was confirmed at
`C:\Users\63923\Desktop\database\backups\DILG-RC\phase-8a\20260728-175019`
before implementation.

## Preserved image artifacts

The preferred model artifacts were copied from `mobile/assets/models` into
`ai-inference-server/models/image`. Originals were not changed.

| Artifact | SHA-256 |
| --- | --- |
| `best_float16.tflite` | `deb4e346701a063cfa39494fd9ab86882269ca827795304db27e60f8e42a7c0f` |
| `labels.txt` | `63c27fd6842efb23a300d5427d066314021c59d2c02fde3aab1c938c0e03cc16` |
| `model_metadata.json` | `30b2ac8a60c281615cc48cf701bad05543772cc18ef42ded38354c6c7e7869cc` |

Automated tests compare every FastAPI copy with its preserved mobile source.

## Runtime and tensor contract

- Target checked: Windows 11 AMD64, Python 3.12.5
- Runtime: `ai-edge-litert==2.1.6`
- Input: Float32 RGB NHWC `[1, 640, 640, 3]`
- Preprocessing: EXIF correction, RGB conversion, aspect-ratio-preserving letterbox,
  RGB `(114, 114, 114)`, normalization by 255
- Output: Float32 `[1, 9, 8400]`
- Classes, in order: `construction_materials`, `garbage_debris`, `illegal_parking`,
  `road_obstruction`, `sidewalk_obstruction`
- Postprocessing: normalized xywh output is converted to the 640x640 input space before
  reversing letterboxing; 0.25 candidate threshold, 0.45 per-class NMS IoU, maximum 20
  detections. Results below 0.60 remain low-confidence evidence requiring staff review.

The real model was loaded, tensor allocation succeeded, and an actual inference was
executed. One interpreter is retained for reuse. Preprocessing occurs outside its lock;
the complete mutable `set_tensor`, `invoke`, and copied `get_tensor` sequence is locked;
decoding and NMS occur after the lock is released.

## HTTP contract

`POST /v1/predict/image` accepts a JPEG or PNG multipart `image`.

`POST /v1/predict/multimodal` accepts:

- `image`
- `text_report`
- `latitude`
- `longitude`
- optional `barangay_hint`

Uploads are bounded to 5 MiB by default. Declared type, decoded type, image validity,
and pixel dimensions are checked. Operational failures use a response shaped as:

```json
{
  "error": {
    "code": "controlled_error_code",
    "message": "Safe client-facing message."
  }
}
```

Successful multimodal results contain separate `image`, `text`, `gis`, `fusion`,
`models`, `timing`, and `review` sections. AI evidence uncertainty appears only in:

```text
review.ai_needs_manual_review
review.ai_manual_review_reasons
```

Approved AI reasons are:

```text
no_image_detection
low_image_confidence
low_text_confidence
image_text_disagreement
unsupported_category
insufficient_text
insufficient_fusion_confidence
```

Barangay routing appears only in:

```text
gis.needs_manual_barangay_review
gis.barangay_assignment_status
```

`barangay_boundary_unavailable` is not an AI reason. The optional `barangay_hint` is
accepted only for transitional request compatibility and cannot establish jurisdiction,
override GIS, or be returned as confirmed.

## Liveness and readiness

- `GET /health` returns HTTP 200 while the process is alive and reports safe,
  per-component status, including degraded state.
- `GET /ready` returns HTTP 503 when the image model/runtime, NLP model, fusion service,
  or municipal boundary is unavailable.
- Missing barangay polygons remain a documented routing limitation and do not alone
  make inference unready.

## Verification

The Phase 8C suite covers:

- source/destination hashes and model tensor contract;
- real LiteRT model loading and inference;
- landscape, portrait, square, RGB, normalization, padding, and EXIF preprocessing;
- decoder mapping and per-class NMS;
- normalized LiteRT coordinate conversion and low-confidence candidate retention;
- malformed, unsupported, and excessive upload rejection;
- controlled no-detection;
- liveness and required-component readiness;
- missing-barangay readiness behavior;
- AI/GIS review separation and non-authoritative barangay hints;
- shared-interpreter serialization;
- existing NLP, GIS, fusion, and health regressions.

No representative positive road-clearing evidence image exists in the repository.
Only logos, icons, and an NLP confusion-matrix image were found, so the roadmap’s
conditional positive-image test is documented as unavailable rather than fabricated.
A validated positive evidence set remains required before production acceptance.

## Recovery

This phase is isolated on `feature/phase-8c-server-side-ai-inference`. To abandon it
before merge, switch back to `main`; do not delete the external Phase 8A backup.
No database migration, cloud resource, deployed service, or remote branch is created by
Phase 8C.
