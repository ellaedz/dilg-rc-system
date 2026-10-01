# CIVICLEAR Security, Identity, and AI Evidence Roadmap

## Document purpose

This roadmap continues the completed CIVICLEAR implementation roadmap. It covers:

1. secure password management for the DILG administrator and 26 barangay accounts;
2. optional identified citizen reporting without removing anonymous reporting;
3. photo metadata and provenance checks;
4. pretrained AI-generated-image detection; and
5. controlled staging, production deployment, and credential cutover.

Complete the phases in order. A phase is complete only when its implementation,
automated tests, evidence record, rollback notes, and review gates have passed.

## Scope correction

CIVICLEAR has exactly five supported AI violation classes:

1. Construction Materials
2. Waste/Garbage or Garbage/Debris
3. Illegal Parking
4. Road Obstruction
5. Sidewalk Obstruction

The existing custom YOLOv8/TFLite model remains the violation detector. This roadmap
does not add vending obstruction, encroachment, abandoned vehicle, illegal structure,
or any other violation class.

AI-generated-image detection is a separate evidence-integrity signal. It must never be
stored or presented as a violation class.

## Non-negotiable safety rules

- Preserve all production reports, photographs, GIS records, AI results, timelines,
  tracking references, users, roles, and barangay assignments.
- Use additive, backward-compatible migrations with safe defaults for existing rows.
- Never run `migrate:fresh`, `db:wipe`, destructive seeders, or automatic reseeding.
- Never automatically rotate a live password.
- Never generate, display, log, export, commit, screenshot, or place a plaintext
  password in a browser URL.
- Never expose an optional reporter name through public tracking, public GIS payloads,
  public exports, logs, or unauthenticated APIs.
- Never call an image authentic, fake, or AI-generated as a proven fact based only on
  a probabilistic detector.
- Never let AI automatically approve, reject, classify officially, route finally, or
  resolve a report.
- Never delay or lose a citizen report because an optional AI analysis is unavailable.
- Keep new evidence-integrity functions behind feature flags until staging acceptance.
- Deploy immutable Azure revisions and retain a verified rollback revision.
- Do not send municipal evidence photographs to an unrelated public inference API.

## Target evidence flow

```text
Citizen mobile application
        |
        | HTTPS report submission
        v
Laravel API
        |-- validates and stores the report
        |-- stores optional reporter name privately
        |-- sanitizes and stores the photograph privately
        |-- calculates evidence hashes
        `-- dispatches background processing
                    |
                    v
Azure Queue / processing worker
        |-- existing five-class YOLO inference
        |-- selected metadata consistency checks
        |-- duplicate and near-duplicate checks
        |-- C2PA provenance verification
        `-- pretrained synthetic-image analysis
                    |
                    v
Authorized staff Evidence Integrity panel
        |
        `-- human review and official decision
```

## State separation

Keep these domains independent:

```text
Report workflow:
Submitted -> For Verification -> Verified/Rejected -> response lifecycle

Violation AI:
pending | processing | completed | failed

Evidence integrity:
pending | processing | completed | inconclusive | failed

Account status:
active | temporarily_locked | disabled

Password status:
current | change_required
```

An evidence warning must not change the report workflow automatically.

---

# Phase 15 - Authentication and Password Security

## Phase 15A - Security Baseline and Recovery Preparation

**Branch:** `chore/phase-15a-security-baseline`

**Objective:** Record a recoverable authentication baseline before changing account
behavior.

### Tasks

- [x] Start from the latest verified `main` and review the complete worktree.
- [x] Record the exact starting commit and deployed Azure revision.
- [x] Back up production PostgreSQL using the approved non-destructive procedure.
- [x] Record user counts by role and assigned barangay without printing credentials.
- [x] Confirm the 26 barangay assignments and authorized DILG administrator account.
- [x] Inspect login, logout, session storage, middleware, authorization, and password
      hashing.
- [x] Confirm production session storage and current session-expiration settings.
- [x] Confirm tests cannot connect to production databases or storage.
- [x] Record the current authentication and authorization test results.
- [x] Document an application and database rollback procedure.

### Exit gate

- [x] Backup and restoration steps are documented and verified.
- [x] Existing account access and role restrictions are recorded.
- [x] No account, password, session, or production data has changed.

---

## Phase 15B - Password and Audit Data Foundation

**Branch:** `feature/phase-15b-password-data-foundation`

**Objective:** Add backward-compatible security state without locking out existing
users.

### Additive schema targets

```text
users.must_change_password
users.password_changed_at
users.failed_login_attempts
users.locked_until
users.last_failed_login_at
users.session_version

password_histories
security_audit_events
```

### Tasks

- [x] Add reversible migrations with safe nullable fields or defaults.
- [x] Set existing users to `must_change_password = false` during migration.
- [x] Do not alter any existing password hash during migration.
- [x] Add password history using hashes only.
- [x] Add security audit events for reset, change, lock, unlock, and rejected attempts.
- [x] Record actor, affected account, action, timestamp, and IP address.
- [x] Prohibit password values and password-like request fields from audit payloads.
- [x] Add centralized password policy validation with a minimum of 16 characters.
- [x] Support compromised/common-password blocking using a local or privacy-preserving
      mechanism.
- [x] Reject the current password and recently used passwords when technically
      possible.

### Required tests

- Existing password hashes and login behavior remain valid.
- Existing users are not forced to change passwords after migration.
- Password policy accepts long passphrases and rejects weak/compromised values.
- Audit records contain required context and no password material.
- Migration rollback does not delete users or reports.

### Exit gate

- [x] Schema is backward compatible.
- [x] No live password was rotated.
- [x] Existing users are not automatically locked out.

---

## Phase 15C - DILG Account Management and Forced Password Change

**Branch:** `feature/phase-15c-account-management`

**Objective:** Let only the DILG administrator securely reset barangay account
passwords and require a private replacement at the next login.

### Tasks

- [x] Add a DILG Administrator-only Account Management page.
- [x] List the 26 barangay accounts with assigned barangay, account state, password
      state, and last password-change date.
- [x] Never display an existing password or password hash.
- [x] Add reset-password and confirmation fields with server-side validation.
- [x] Hash the temporary password before persistence.
- [x] Set `must_change_password = true` after an administrator reset.
- [x] Invalidate all existing sessions for the affected account.
- [x] Add a Change Password page for every authenticated user.
- [x] Require the current password for a normal self-service change.
- [x] Require a new private password immediately after temporary-password login.
- [x] Allow only logout and the required password-change flow while change is required.
- [x] Regenerate the current session after a successful password change.
- [x] Invalidate all other sessions after the change.
- [x] Record reset and change audit events without sensitive form values.
- [x] Preserve DILG and barangay role restrictions.

### Required tests

- DILG administrator can reset a barangay account.
- Barangay staff cannot list accounts or reset another account.
- A temporary password forces an immediate change.
- A forced-change user cannot access dashboards, reports, GIS, analytics, or exports.
- Incorrect current passwords are rejected.
- Password confirmation and 16-character minimum are enforced.
- Current and recently used passwords are rejected.
- Other sessions are invalidated after reset and change.
- Current session regeneration succeeds after self-service change.
- Passwords do not appear in HTML, JSON, logs, exceptions, audit events, or tests.

### Exit gate

- [x] All account-management authorization tests pass.
- [x] Every password remains one-way hashed.
- [x] No production account has been reset.

---

## Phase 15D - Login Protection and Account Recovery Instructions

**Branch:** `feature/phase-15d-login-protection`

**Objective:** Reduce password guessing and account enumeration without creating a
permanent denial-of-service risk.

### Tasks

- [ ] Normalize email addresses consistently before authentication and throttling.
- [ ] Rate-limit login using both normalized email and client IP.
- [ ] Add escalating temporary lockouts with a documented maximum duration.
- [ ] Avoid permanent lockout based solely on unauthenticated failures.
- [ ] Return a generic error for nonexistent users, wrong passwords, and locked
      accounts.
- [ ] Audit locking and unlocking without logging credentials.
- [ ] Add an authorized DILG unlock action.
- [ ] Replace the fake Forgot Password action with instructions to contact the
      authorized DILG administrator.
- [ ] Preserve CSRF, secure session cookies, authorization, and server-side validation.

### Required tests

- Per-email and per-IP rate limits.
- Lockout activation and expiration.
- Successful login after lockout expiration.
- Generic errors do not reveal whether an account exists.
- Authorized manual unlock and unauthorized unlock rejection.
- Audit history for lock and unlock actions.

### Exit gate

- [ ] Authentication regression suite passes.
- [ ] Lockout cannot become permanent through normal failed-login traffic.
- [ ] Production password rotation remains unstarted.

---

# Phase 16 - Optional Reporter Identity and Privacy

## Phase 16A - Private Optional Reporter Name

**Branch:** `feature/phase-16a-optional-reporter-name`

**Objective:** Support identified or anonymous reports while protecting reporter
privacy.

### Tasks

- [ ] Add a nullable reporter-name field without rewriting existing reports.
- [ ] Add an optional Name field to the Android report form.
- [ ] Explain that leaving the field blank submits anonymously.
- [ ] Add an approved privacy notice describing purpose, access, and retention.
- [ ] Normalize and validate the name server-side.
- [ ] Apply a reasonable maximum length and reject markup/control characters.
- [ ] Update drafts, retries, idempotency, offline recovery, and mobile API types.
- [ ] Limit access to authorized DILG staff and the assigned barangay staff.
- [ ] Exclude the name from public tracking, GIS marker APIs, analytics responses,
      public exports, URLs, and logs.
- [ ] Preserve all existing anonymous reports unchanged.

### Required tests

- Identified and anonymous submission.
- Mobile retry and idempotent replay.
- Role and barangay-based viewing authorization.
- Public tracking and GIS privacy regression.
- Validation and injection attempts.
- Existing reports remain unchanged.

### Exit gate

- [ ] Anonymous reporting still works.
- [ ] Authorized staff can view an intentionally supplied name.
- [ ] No public endpoint leaks the name.

---

# Phase 17 - Photo Metadata and Provenance

## Phase 17A - Privacy-Preserving Metadata Capture

**Branch:** `feature/phase-17a-photo-metadata-capture`

**Objective:** Capture selected evidence signals before mobile sanitization without
retaining unnecessary device information.

### Tasks

- [ ] Record camera or gallery source.
- [ ] Capture available image time, dimensions, MIME type, and file size.
- [ ] Capture image GPS only when available and permitted.
- [ ] Preserve app-submitted GPS, accuracy, and server receipt time as separate fields.
- [ ] Capture only a limited editing/software indicator when available.
- [ ] Do not upload or retain the complete raw EXIF block.
- [ ] Exclude device serial numbers and unrelated hardware identifiers.
- [ ] Continue storing only the sanitized report photograph.
- [ ] Calculate SHA-256 server-side for the stored evidence file.
- [ ] Record explicit states for present, missing, unsupported, and malformed metadata.

### Required tests

- Camera and gallery flows.
- Images with, without, and with malformed EXIF.
- Orientation and image sanitization.
- Permission denial and metadata-unavailable behavior.
- No raw EXIF or prohibited device identifier persistence.

### Exit gate

- [ ] Submission works when all optional metadata is missing.
- [ ] The stored photograph remains sanitized and private.
- [ ] Selected metadata is separated from authoritative workflow data.

---

## Phase 17B - Consistency, Duplicate, and C2PA Checks

**Branch:** `feature/phase-17b-evidence-provenance-checks`

**Objective:** Produce explainable evidence warnings without claiming authenticity.

### Tasks

- [ ] Compare capture time, submission time, and server receipt time.
- [ ] Compare image GPS with app-submitted GPS using a documented tolerance.
- [ ] Add a perceptual hash for near-duplicate detection.
- [ ] Keep exact-hash and near-duplicate matches distinct.
- [ ] Verify C2PA Content Credentials when present.
- [ ] Validate C2PA signatures and record issuer/provenance safely.
- [ ] Treat missing EXIF or C2PA as unavailable, not suspicious by itself.
- [ ] Produce individual explainable findings rather than an unsupported authenticity
      claim.
- [ ] Run checks asynchronously and independently from report submission.

### Required tests

- Matching and conflicting times and locations.
- Exact duplicate, near duplicate, and unrelated photographs.
- Valid, invalid, malformed, and missing C2PA records.
- Failed check does not lose or reject a report.
- Public API privacy regression.

### Exit gate

- [ ] Every warning names the signal that caused it.
- [ ] No warning automatically changes report status.
- [ ] No result is labelled proof of authenticity.

---

# Phase 18 - Pretrained AI-Generated-Image Detection

## Phase 18A - Model Due Diligence and Local Benchmark

**Branch:** `research/phase-18a-synthetic-image-detector`

**Objective:** Decide whether the pretrained detector is suitable before connecting it
to live report processing.

### Candidate

Evaluate the official Community Forensics checkpoint:

```text
OwensLab/commfor-model-384
```

Do not commit model weights directly to Git.

### Tasks

- [ ] Verify the official source, model card, license, dependencies, revision, and
      SHA-256 checksum.
- [ ] Document expected input preprocessing and output interpretation.
- [ ] Load the model once during FastAPI startup.
- [ ] Create a local inference adapter isolated from the five-class YOLO detector.
- [ ] Test representative real road photographs and synthetic images from multiple
      generator families.
- [ ] Include screenshots, crops, resized images, recompressed images, edited images,
      and low-light mobile photographs.
- [ ] Measure false positives, false negatives, latency, cold start, CPU, and memory.
- [ ] Define three outcomes: synthetic indicators detected, no strong synthetic signal,
      and inconclusive.
- [ ] Avoid selecting thresholds solely from the model's original benchmark.
- [ ] Record why the model is accepted, deferred, or rejected.

### Exit gate

- [ ] Model provenance and license review pass.
- [ ] The model runs within an acceptable Azure resource envelope.
- [ ] Known limitations and local test results are recorded.
- [ ] No production behavior has changed.

---

## Phase 18B - Asynchronous Shadow-Mode Integration

**Branch:** `feature/phase-18b-synthetic-image-shadow-mode`

**Objective:** Analyze new evidence without delaying submissions or influencing staff
decisions automatically.

### Processing states

```text
pending
processing
completed
inconclusive
failed
```

### Tasks

- [ ] Add additive analysis tables/fields with model name, revision, score, outcome,
      duration, timestamp, and safe error category.
- [ ] Dispatch analysis through the existing durable background-processing flow.
- [ ] Apply bounded timeouts and retries.
- [ ] Never replace a completed result with a later unavailable result.
- [ ] Keep report submission successful when analysis times out or fails.
- [ ] Keep the detector behind a disabled-by-default feature flag.
- [ ] Enable shadow mode in staging so results cannot alter workflow or official
      classification.
- [ ] Do not expose detector scores to citizens or public APIs.
- [ ] Record model version with every result for auditability.

### Required tests

- Successful, inconclusive, timeout, malformed-image, and model-unavailable cases.
- Duplicate job delivery and idempotency.
- Retry exhaustion and recovery.
- Submission latency and mobile polling regression.
- Separation from the five-class violation prediction.
- No automatic rejection or official classification.

### Exit gate

- [ ] Mobile receives a tracking reference without waiting for this detector.
- [ ] Queue and AI failures preserve the report and photograph.
- [ ] Shadow results remain authorized-staff-only.

---

## Phase 18C - Evidence Integrity Review Panel

**Branch:** `feature/phase-18c-evidence-integrity-panel`

**Objective:** Present all evidence signals clearly to authorized reviewers.

### Panel contents

- Existing five-class YOLO recommendation and confidence
- Synthetic-image analysis outcome
- Metadata availability and consistency
- Capture-time consistency
- GPS consistency
- Exact and near-duplicate findings
- C2PA provenance result
- Model/version and processing timestamp
- Plain-language limitations
- Final human decision and existing workflow controls

### UI rules

- Use green for consistent, amber for review recommended, red for strong warning,
  gray for unavailable/inconclusive, and blue for processing.
- Label the combined display **Evidence Review Recommendation**.
- Do not label it **Authenticity Score**.
- Show the notice that automated results are investigative indicators only.
- Do not make a warning the visual equivalent of a staff rejection.
- Preserve mobile and narrow-screen accessibility.

### Required tests

- DILG and assigned-barangay authorization.
- Cross-barangay access denial.
- Every processing and availability state.
- No result leakage through public tracking, GIS, exports, or logs.
- Existing staff verification remains authoritative.

### Exit gate

- [ ] Staff can understand which signal produced each warning.
- [ ] The panel cannot change official classification automatically.
- [ ] Privacy and authorization tests pass.

---

# Phase 19 - Acceptance, Deployment, and Password Cutover

## Phase 19A - Full Regression and Staging Acceptance

**Branch:** `test/phase-19a-security-ai-acceptance`

**Objective:** Prove the complete upgrade without changing live credentials.

### Tasks

- [ ] Run the complete Laravel regression suite.
- [ ] Run FastAPI tests, mobile type checks/tests, formatting, static analysis, and
      dependency/security checks.
- [ ] Verify web login, forced password change, account management, reports, GIS,
      analytics, CSV/PDF exports, public tracking, and mobile submission.
- [ ] Verify all 26 barangay assignments remain unchanged.
- [ ] Verify report, image, timeline, and GIS record counts against the baseline.
- [ ] Build immutable Laravel and FastAPI images from the reviewed commit.
- [ ] Deploy a no-production-traffic Azure staging revision.
- [ ] Test with designated test accounts and test reports only.
- [ ] Record false-positive, false-negative, performance, privacy, and resource results.
- [ ] Complete an application and database rollback drill.

### Exit gate

- [ ] All required tests pass or every accepted limitation is documented.
- [ ] No production password has changed.
- [ ] No production data has been deleted or reseeded.
- [ ] DILG has reviewed the password reset and Evidence Integrity workflows.

---

## Phase 19B - Production Deployment Without Credential Rotation

**Branch:** `release/phase-19b-security-ai-deployment`

**Objective:** Deploy the tested features safely while leaving current credentials
unchanged.

### Tasks

- [ ] Take and verify final production backups.
- [ ] Deploy immutable revisions and run additive migrations once.
- [ ] Keep the previous healthy revision available for rollback.
- [ ] Verify existing accounts can still log in.
- [ ] Verify all existing reports and assignments remain accessible.
- [ ] Verify new features remain behind approved feature flags.
- [ ] Verify monitoring and sanitized security logs.
- [ ] Enable account management for the authorized DILG administrator.
- [ ] Keep synthetic-image detection in shadow mode initially.
- [ ] Do not rotate any account password in this phase.

### Exit gate

- [ ] Production health and regression checks pass.
- [ ] Rollback remains available.
- [ ] Password rotation awaits a separate explicit approval.

---

## Phase 19C - Controlled Password Cutover

**Branch:** No application-code branch unless a cutover defect requires a reviewed fix.

**Objective:** Allow authorized DILG personnel to assign unique temporary passwords
without exposing them to developers or repositories.

### Cutover procedure

1. Obtain explicit written approval and schedule a maintenance window.
2. Confirm the authorized DILG officer who will perform resets.
3. Confirm the rollback and account-recovery contacts.
4. The DILG officer enters a unique temporary password directly into the protected
   Account Management page for one account at a time.
5. Do not place passwords in chat, GitHub, spreadsheets, PDFs, screenshots, email, or
   system documentation.
6. Distribute each temporary password through the municipality's approved confidential
   channel.
7. Require the account owner to sign in and choose a private new password immediately.
8. Confirm the old sessions are invalidated and the audit event contains no password.
9. Complete a small pilot group before continuing through all 26 barangay accounts.
10. Reset the DILG administrator password through the approved self-service procedure,
    with recovery ownership confirmed first.
11. Record completion status only, never the password itself.

### Exit gate

- [ ] Every intended account has a unique user-controlled password.
- [ ] Every temporary password has been replaced.
- [ ] Old sessions are invalidated.
- [ ] No plaintext password exists in project artifacts or logs.
- [ ] Account access and barangay assignment checks pass.

---

## Recommended branch order

```text
1.  chore/phase-15a-security-baseline
2.  feature/phase-15b-password-data-foundation
3.  feature/phase-15c-account-management
4.  feature/phase-15d-login-protection
5.  feature/phase-16a-optional-reporter-name
6.  feature/phase-17a-photo-metadata-capture
7.  feature/phase-17b-evidence-provenance-checks
8.  research/phase-18a-synthetic-image-detector
9.  feature/phase-18b-synthetic-image-shadow-mode
10. feature/phase-18c-evidence-integrity-panel
11. test/phase-19a-security-ai-acceptance
12. release/phase-19b-security-ai-deployment
13. Phase 19C controlled operational cutover
```

## Immediate next action

Phase 15C is complete locally and documented in
`docs/phase-records/PHASE_15C_ACCOUNT_MANAGEMENT.md`. Begin Phase 15D only from the
reviewed Phase 15C commit. Phase 15D may add normalized email/IP throttling, bounded
temporary lockouts, generic login failures, and authorized unlocking. It must not reset
a production password, rotate a live credential, or deploy a migration to production.
