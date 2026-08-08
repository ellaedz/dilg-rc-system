# Phase 8F-0 — Mobile Unclassified Submission Contract

## Baseline

- Starting branch: `main`
- Starting commit: `e71829c3d46e3c80a0a2b66d476c2ec64f1e4090`
- Implementation branch: `fix/phase-8f-mobile-unclassified-contract`
- Planned commit: `fix: support unclassified mobile submissions`

The final commit SHA is reported after the commit is created. It is not inserted into a
file in that same commit.

## Purpose

Phase 8F makes Laravel and FastAPI, rather than the phone, responsible for advisory AI
classification. Before the mobile application can stop sending its transitional
classification field, Laravel must be able to store a report that has no citizen
classification without inventing a citizen choice.

Phase 8F-0 is a narrow server-contract prerequisite. It does not implement the Phase 8F
mobile flow, change database schema, remove TFLite, migrate to Supabase, or deploy
cloud infrastructure.

## Storage gate

Repository inspection confirmed that `violation_reports.selected_violation_type` is a
plain, non-null string column. No later migration changes the column or applies a
database check constraint. The model has no enum cast, accessor, mutator, or category
constant that restricts stored values.

The internal value `Unclassified` can therefore satisfy the existing column without a
schema migration or a destructive change. The value is defined only by
`CitizenViolationType::UNCLASSIFIED`.

`Other Road Clearing Violation` remains a genuine citizen-selectable legacy category.
It is never used as the internal unclassified state.

## Ownership contract

When the mobile submission omits `selected_violation_type`, Laravel stores the internal
sentinel. When a legacy client supplies the field, validation accepts only configured
citizen-selectable categories.

A client cannot explicitly submit `Unclassified`. The normal Laravel validation
response rejects it with HTTP 422 because it is not in the citizen category allowlist.

The internal sentinel means only:

```text
No citizen classification was supplied.
```

It does not mean:

- an AI classification;
- an official staff classification;
- a verified violation;
- the generic “Other Road Clearing Violation” category.

New records remain:

```text
verification_status = Pending
official_violation_type = null
verified_by = null
verified_at = null
```

Server AI may independently populate `ai_possible_violation` and its advisory evidence.
It never changes the citizen or official classification.

## Idempotency

The submission fingerprint normalizes an omitted citizen category to the same internal
sentinel that is stored on the report. An authorized idempotent replay therefore
reconstructs the same canonical payload.

Reusing the same Idempotency-Key with a later citizen category is a different payload
and returns `IDEMPOTENCY_PAYLOAD_CONFLICT`. It cannot rewrite the original report.

## API and presentation semantics

Mobile and public resources never return the literal sentinel as a citizen choice.
They expose:

```text
citizen_selected_violation_type = null
has_citizen_classification = false
```

The transitional mobile `selected_violation_type` field is also null for an internally
unclassified report. Existing classified reports continue returning their genuine
citizen category.

Authenticated staff interfaces display:

```text
Awaiting Staff Classification
```

Citizen-category charts, rankings, print summaries, and GIS violation counts exclude
the internal sentinel. Total operational report counts continue including the report
because it is still a valid submitted record.

The citizen violation-type endpoint and existing filter options continue listing only
genuine selectable categories.

## Verification

The focused Phase 8F-0 suite covers:

- omitted citizen classification;
- client rejection of the internal sentinel;
- legacy category compatibility;
- public/mobile resource privacy;
- canonical idempotent replay;
- payload-conflict protection;
- server-AI separation;
- official-field separation;
- analytics exclusion;
- staff-friendly presentation.

All Laravel test commands use PHPUnit’s protected configuration:

```text
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
DB_URL=
```

Completed verification:

- focused Phase 8F-0 suite: 8 tests, 63 assertions;
- full Laravel suite: 95 tests, 759 assertions;
- Blade compilation: passed;
- Laravel Pint on changed PHP files: passed.

No real database, normal private-photo directory, `.env`, mobile dependency, or native
project was changed.

## Recovery

Before merge, Phase 8F-0 can be abandoned by switching back to `main`. No database
migration or data rollback is required.

After merge, recovery is a normal code revert of the Phase 8F-0 commit. Existing
sentinel rows must be reviewed before reverting because the prior controller requires a
non-null citizen category. Never rewrite such rows to another category without
authorized classification evidence.
