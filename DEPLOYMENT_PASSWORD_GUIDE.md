# DILG-RC Password Deployment Guide

Passwords and recovery credentials must never be stored in this repository.

## Initial provisioning

1. Generate a strong temporary password with an approved password manager.
2. Store it in the deployment secret manager as `SEED_DEFAULT_PASSWORD`.
3. Run `php artisan db:seed --class=UserSeeder` in the intended environment.
4. Assign a unique password to every administrator and barangay account immediately.
5. Remove `SEED_DEFAULT_PASSWORD` from the deployment environment after provisioning.

`UserSeeder` refuses to run if the temporary password is missing or shorter than 12 characters.

## Password requirements

- Use a unique value for every account.
- Prefer randomly generated passwords of at least 16 characters.
- Do not derive passwords from barangay names, dates, or email addresses.
- Do not reuse a seeding password as a permanent account password.
- Require a secure out-of-band change at first use when the deployment supports it.

## Distribution

- Send credentials only through an approved organizational password manager or sealed official channel.
- Never send passwords through ordinary email, SMS, source control, tickets, screenshots, or chat.
- Record who received each credential and when it was rotated, without recording the password itself.

## Reset procedure

1. Verify the requester through the approved DILG identity process.
2. Generate a new random temporary password in the password manager.
3. Update the account from an authenticated administrative environment.
4. Deliver the temporary value through the approved secure channel.
5. Invalidate existing sessions and require another change at first use when supported.

## Incident response

If a credential is committed or otherwise disclosed:

1. Revoke or rotate it immediately.
2. Review authentication and administrative logs for misuse.
3. Remove the value from the current repository and rewrite Git history.
4. Force-push the cleaned history and have collaborators re-clone.
5. Document the incident without copying the exposed value.

History rewriting does not invalidate an exposed credential. Rotation is always required.
