# DILG-RC System Database

## Database Location
- **File**: `database/database.sqlite`
- **Backup**: `database/DILG-RC_backup.sqlite`
- **Type**: SQLite 3

## For Your Classmate

### Option 1: Copy SQLite File (Recommended)
1. Copy the file: `database/database.sqlite`
2. Place it in your Laravel project's `database/` folder
3. Update your `.env` file:
```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### Option 2: Fresh Installation
If you want to start fresh:
```bash
php artisan migrate:fresh
php artisan db:seed
```

## Database Tables

### Users Table
- `id` - Primary key
- `email` - User email (unique)
- `[removed-credential]` - Hashed password
- `role` - 'dilg_admin' or 'barangay_staff'
- `assigned_barangay` - For barangay staff only
- `created_at`, `updated_at`

### Violation Reports Table (violation_reports)
- `id` - Primary key
- `report_id` - Unique tracking ID (e.g., RCV-2026-0001)
- `submitted_by` - Reporter name (now "Anonymous Citizen")
- `contact_number` - Optional contact
- `selected_violation_type` - Type of violation
- `description` - Report description
- `image_path` - Photo evidence path
- `latitude`, `longitude` - GPS coordinates
- `gps_accuracy` - GPS accuracy
- `detected_barangay` - Auto-assigned barangay
- `assigned_barangay_office` - Office handling the report
- `status` - Current status (Submitted, Verified, In Progress, etc.)
- `verification_status` - Verification state
- `assigned_personnel` - Staff handling the report
- `action_taken` - Actions performed
- `timestamp` - Report submission time
- `date_submitted`, `date_updated`
- `created_at`, `updated_at`

### Report Timeline Table (report_timelines)
- `id` - Primary key
- `report_id` - Foreign key to violation_reports
- `status` - Status at this point in time
- `action` - Description of action
- `updated_by` - User who made the update
- `created_at`

### Complaints Table
- Legacy table (may not be in active use)

## Test Accounts

### DILG Admin
- **Email**: dilg@admin.com
- **Password**: [removed-credential]
- **Role**: dilg_admin

### Barangay Staff Examples
- **Bagumbayan**: bagumbayan@staff.com / [removed-credential]
- **Bubukal**: bubukal@staff.com / [removed-credential]
- **Duhat**: duhat@staff.com / [removed-credential]
- **Jasaan**: jasaan@staff.com / [removed-credential]
- **Pagsawitan**: pagsawitan@staff.com / [removed-credential]
- **Palasan**: palasan@staff.com / [removed-credential]

## Important Notes

1. **Anonymous Reporting**: Reports are now anonymous - `submitted_by` is always "Anonymous Citizen"
2. **Tracking ID**: Citizens use `report_id` to track their reports
3. **GPS Assignment**: Reports are auto-assigned to barangays based on GPS coordinates
4. **Phase**: Currently in Phase 4A.1 (UI Redesign complete), moving to Phase 4C (GPS Auto-Assignment)

## Database Migrations

All migrations are in: `database/migrations/`

Key migration files:
- `0001_01_01_000000_create_users_table.php`
- `2026_XX_XX_XXXXXX_create_violation_reports_table.php` (check actual filename)
- `2026_XX_XX_XXXXXX_create_report_timelines_table.php`

## Seeders

Located in: `database/seeders/`

To seed test data:
```bash
php artisan db:seed
```

## Sharing with Your Classmate

**Send them**:
1. This `DATABASE_INFO.md` file
2. The `database/database.sqlite` file
3. Instructions to update their `.env` file

**Alternative**: If they need SQL format:
- They can open the SQLite file with DB Browser for SQLite
- Export as SQL dump
- Share the SQL file

## Database Tools

**Recommended tools to view/edit SQLite**:
- DB Browser for SQLite (https://sqlitebrowser.org/)
- DBeaver (https://dbeaver.io/)
- TablePlus (https://tableplus.com/)
- Laravel Telescope (if installed)

