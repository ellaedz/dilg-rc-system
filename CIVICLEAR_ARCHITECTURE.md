# CIVICLEAR Approved Target Architecture

## Status

The architecture is approved but not yet implemented.

The current repository remains a Laravel 12 application using local SQLite, local
FastAPI NLP/GIS/fusion services, and an Expo mobile application with on-device TFLite
image inference. The following is the controlled cloud target for Phases 8–14.

## Target system

```text
Citizen Mobile Application
        │
        │ HTTPS multipart submission
        ▼
Public Laravel API on Google Cloud Run
        ├── Supabase PostgreSQL
        ├── Private Supabase Storage
        └── Google Cloud Tasks
                    │
                    ▼
        Protected Laravel processing endpoint
                    │
                    ▼
        Private FastAPI service on Google Cloud Run
                    ├── Server-side image inference
                    ├── NLP classification
                    ├── Municipality GIS validation
                    └── Multimodal fusion
```

## Responsibilities and trust

- Mobile captures or selects a photograph, collects text and GPS, creates one
  idempotency key, submits to Laravel, stores the returned Tracking Token, and polls
  Laravel.
- Mobile does not run required AI inference and does not submit trusted predictions,
  confidence values, official classifications, or barangay jurisdiction.
- Laravel is the only public application API and controls validation, authorization,
  workflow, identifiers, idempotency, storage, task creation, AI orchestration, and safe
  tracking responses.
- FastAPI performs authenticated server-side image, NLP, GIS, and fusion processing.
- AI output is advisory. Assigned barangay staff establish the official classification.
- DILG monitors reports, performs temporary manual barangay routing, and retries failed
  AI processing when authorized.

## Durable submission

Laravel creates the report before attempting photograph storage or AI. It generates:

- a concurrency-safe human-readable Report Number;
- a cryptographically random public Tracking Token whose keyed hash is stored;
- a keyed hash of the mobile idempotency key; and
- an internal report UUID.

Laravel validates and sanitizes the photograph, corrects orientation, removes
unnecessary EXIF metadata, and stores only a private object key. Failed uploads resume
against the same report and idempotency key.

After durable report and photograph storage, Laravel creates a Cloud Task. Failed task
creation is recorded and recovered by a controlled job. It does not invalidate the
citizen report.

## Separate state domains

The system keeps these concepts independent:

- `report_status`: official case lifecycle;
- `verification_status`: validity decision;
- `ai_processing_status`: pending, processing, completed, or failed;
- `photo_upload_status`;
- `task_creation_status`;
- `barangay_assignment_status`;
- `ai_needs_manual_review` and `ai_manual_review_reason`;
- `needs_manual_barangay_review`; and
- operational `processing_error_code` and safe diagnostic message.

Infrastructure failures are not AI evidence-review reasons. Missing barangay polygons
are a routing limitation, not an AI or submission failure.

## AI and official classifications

FastAPI returns separate image, text, GIS, and fusion results. Laravel saves the
advisory value as `ai_possible_violation`.

AI never fills `official_violation_type`. Authorized assigned barangay staff verify or
correct the evidence and record:

- `official_violation_type`;
- `verification_status`;
- `verified_by`; and
- `verified_at`.

The original AI recommendation remains available for evaluation.

## GIS contract

The current `public/gis/boundary.geojson` is a validated Santa Cruz municipal boundary.
Its authoritative government provenance has not yet been established, so it must not
be described as official.

The 26 barangay hall points are office references and routing recommendations only.
They are not jurisdiction boundaries.

Exact barangay detection remains unavailable until a validated Polygon or MultiPolygon
dataset is obtained. Until then, DILG manual routing remains the correct workflow.

## Security and privacy

- FastAPI is private and invoked by Laravel using Google Cloud OIDC service identity.
- Supabase database, Storage, and service-role credentials remain server-side.
- The photograph bucket is private; staff access uses short-lived authorized URLs.
- Public tracking uses the unguessable Tracking Token, not only the Report Number.
- Public responses exclude internal IDs, private paths, staff notes, raw AI responses,
  stack traces, and private citizen information.
- Test/demo reports are explicitly marked with `is_test_data`; they are never inferred
  from descriptions or names.

## Official analytics

Operational maps may show multiple lifecycle and validation states.

Official statistics include only non-test, verified, valid, inside-jurisdiction,
non-duplicate reports with an official classification. They use verification evidence
such as `verified_at` rather than requiring `report_status = Verified`, ensuring that
Assigned, Resolved, and Closed valid reports remain counted.

## Implementation authority

[`CIVICLEAR_IMPLEMENTATION_ROADMAP.md`](CIVICLEAR_IMPLEMENTATION_ROADMAP.md) defines the
approved phase order, branch names, safety gates, tests, exclusions, and completion
criteria.

