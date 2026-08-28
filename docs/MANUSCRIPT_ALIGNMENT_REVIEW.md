# CIVICLEAR Manuscript Alignment Review

## Review scope

- Source reviewed: `C:\Users\63923\Documents\civiclear 1 to 3.docx`
- Review date: 2026-08-28
- Manuscript coverage: Chapters 1-3, 156 Word-computed pages
- Purpose: compare the manuscript's claimed system design with the approved CIVICLEAR
  roadmap and observed implementation state before Phase 10B.

This file records alignment findings only. It does not authorize implementation,
deployment, manuscript editing, or a change to phase ownership.

## Overall result

**Conditionally aligned.** The research problem, intended workflow, technology stack,
server-side multimodal AI, Supabase data layer, staff-verification principle, and MPDO
polygon methodology agree with the approved CIVICLEAR direction. Several passages must
be corrected or held in future/proposed tense until their implementation phases and
acceptance tests are complete.

## Aligned statements

- The citizen application submits text, photographic evidence, GPS coordinates, and a
  timestamp to a Laravel-controlled workflow.
- Laravel is the public API and workflow authority.
- FastAPI provides server-side text, image, GIS, and multimodal inference.
- Text inference uses TF-IDF with the selected Logistic Regression model; the manuscript
  may still describe Multinomial Naive Bayes as a compared research model.
- Image inference uses the trained YOLOv8s model for five visible violation classes.
- Supabase PostgreSQL stores relational application data.
- Supabase Private Storage stores report photographs privately.
- AI output is advisory. Authorized staff establish the official verification result.
- Barangay jurisdiction must use validated administrative polygons and point-in-polygon
  analysis, never the nearest barangay hall point.
- The geographic scope is Santa Cruz, Laguna and its 26 barangays.

## Required manuscript corrections

### 1. Remove citizen-selected violation type from the normal mobile flow

The abstract, scope, citizen-beneficiary description, and system-architecture narrative
currently say that citizens submit a selected concern type.

Approved behavior:

- the citizen supplies the description, photograph, GPS coordinates, and timestamp;
- server-side AI automatically produces the advisory violation category;
- staff see that AI result and verify, reject, or correct it;
- citizens must not be required to classify their own reports;
- any legacy `selected_violation_type` field is an internal compatibility concern and
  must not be presented as the normal citizen workflow.

### 2. Do not claim completed MPDO polygon integration yet

The manuscript currently states that validated MPDO barangay polygons are already used
and that point-in-polygon barangay assignment has already been functionally tested.

Observed state:

- `local-boundary.gpkg` has been obtained from MPDO and inspected read-only;
- it contains the expected municipal and 26 barangay polygon layers/features;
- repository integration, controlled conversion/packaging, edge-case validation, and
  known-coordinate acceptance for every barangay remain Phase 13A work.

Until Phase 13A passes, use wording such as "will use," "is designed to use," or
"obtained for controlled integration." Change to completed tense only after the final
artifact, hashes, tests, and physical/operational acceptance are recorded.

### 3. Do not claim Docker or cloud deployment as completed before evidence exists

The manuscript says Docker and Docker Compose were used. The repository contains
containerization preparation, but a successful reviewed image build and Azure deployment
have not yet been established.

Before Phase 10B/10C completion, describe these as prepared container definitions or the
planned deployment foundation. After successful deployment, update the tools and system
architecture sections with observed Azure components only:

- Azure Container Apps;
- Azure Queue Storage;
- event-driven Container Apps Job;
- Azure Container Registry or the separately approved build path;
- Azure Key Vault;
- Microsoft Entra managed identities and protected service roles.

Do not retain Google Cloud Run or Google Cloud Tasks as the final deployed architecture
if Azure becomes the verified deployment provider.

### 4. Clarify automatic AI processing versus staff verification

The manuscript's human-verification principle is correct, but the operational wording
should be explicit:

- AI runs automatically after a successful report submission;
- staff do not manually start AI for each report during the normal workflow;
- the AI category is displayed as an advisory result;
- staff verify, reject, or correct that result to establish the official outcome.

### 5. Keep citizen tracking simple without weakening backend security

If tracking implementation details are added to the manuscript, state that:

- citizens normally see and recognize the human-readable Report Number under My Reports;
- the opaque Tracking Token remains stored securely and is used internally;
- public backend lookup is never authorized by a sequential Report Number alone;
- the normal citizen UI does not require manual Tracking Token entry.

### 6. Avoid unsupported fusion or performance claims

Do not publish fixed text/image weighting such as `65% text + 35% image` unless it is
implemented, tested, and supported by the selected research methodology. The current
server fusion logic is evidence- and confidence-driven rather than that fixed weighting.

Model accuracy and GIS success claims must cite the final observed test set, metrics,
artifact hashes, and acceptance conditions. Do not convert planned tests into completed
results.

## Documentation sequencing

1. Preserve the manuscript as the source document; do not commit the binary DOCX merely
   for convenience.
2. Correct the citizen-input wording now because the approved Phase 8F contract already
   supports server-side classification without a citizen-selected category.
3. Keep Azure deployment statements prospective until Phase 10B and Phase 10C pass.
4. Keep automatic barangay-assignment statements prospective until Phase 13A passes.
5. After those phases, update the architecture figure and its narrative using the exact
   observed deployment and GIS artifacts.
6. Before final defense/submission, reconcile all abstract, scope, methodology,
   architecture, testing, conclusion, and recommendation statements so tense and claimed
   completion evidence are consistent.

## Roadmap impact

No phase order changes are required.

- Phase 10B remains Azure secure deployment migration.
- Phase 10C remains cloud end-to-end verification.
- Phase 11 remains staff verification workflow.
- Phase 12 remains operational GIS and official analytics.
- Phase 13A remains validated MPDO barangay polygon integration.
- Phase 14 remains production hardening, municipal acceptance, and thesis demonstration.

The roadmap's existing Google-specific top-level target flow and Phase 10B/10C labels
must be revised only through the approved Azure Phase 10B documentation workflow. This
review does not modify those roadmap sections.
