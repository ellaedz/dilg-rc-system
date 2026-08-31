# DILG-RC Account Provisioning

This repository intentionally contains no passwords, access tokens, or production credentials.

## Seeded account structure

`Database\Seeders\UserSeeder` creates:

- one DILG administrator account;
- one barangay staff account for each configured Santa Cruz barangay.

Before running the seeder, define a strong temporary `SEED_DEFAULT_PASSWORD` in the local `.env` file or deployment secret store. The value must be at least 12 characters and must never be committed.

```powershell
php artisan db:seed --class=UserSeeder
```

## Required deployment steps

1. Use an approved secret manager to supply `SEED_DEFAULT_PASSWORD`.
2. Run the seeder only in the intended environment.
3. Assign a unique password to every administrator and barangay account immediately after seeding.
4. Remove the temporary seeding secret after account provisioning.
5. Distribute credentials through an approved secure channel.
6. Require password rotation and revoke accounts that are no longer needed.

Never publish credentials in source code, documentation, screenshots, tickets, chat, or Git history.
