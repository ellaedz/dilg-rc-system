# Phase 11A — Staff Verification Workflow

## Status

The Phase 11A source implementation is complete on
`feature/phase-11a-staff-verification-workflow` and is ready for web preview and
deployment approval.

## Staff workflow

- The barangay verification queue displays the completed AI suggestion and
  combined confidence automatically.
- AI output remains advisory and cannot populate official verification fields.
- Assigned barangay staff can confirm the suggested class or select a corrected
  official class.
- A correction requires a brief reason and preserves the original AI category
  and confidence.
- Staff may instead classify a report as invalid, duplicate, outside the
  jurisdiction, or having insufficient evidence.
- Every decision stores the staff member, time, outcome, AI snapshot, agreement
  result, and a report timeline entry.
- A report can receive only one verification decision through the queue.

## Authorization and workflow protection

- Barangay staff can decide only reports belonging to their assigned effective
  barangay.
- DILG administrators retain monitoring, manual barangay routing, and AI retry,
  but cannot submit barangay verification or response decisions.
- Generic response-status updates cannot be used to mark a report Verified or
  Rejected and cannot start before official verification.
- Assignment and the verified queue require both `Valid Violation` and a stored
  `official_violation_type`.

## Validation

The complete Laravel suite passed on 2026-09-09:

```text
177 tests passed
1,218 assertions
```

The new focused tests cover AI agreement, explicit correction, duplicate
classification, effective-barangay authorization, DILG read-only monitoring,
bypass prevention, decision immutability, and timeline recording.

## Deployment note

The additive database migration must run with the Phase 11A web deployment. It
adds nullable AI-at-verification and staff-comparison fields; it does not rewrite
existing report decisions.
