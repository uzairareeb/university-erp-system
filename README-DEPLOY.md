# University ERP — Vercel deploy notes

Original repo: areebaathar-dev/university-erp-system (MIT, PHP + MySQL).
This copy adds what was missing for a live deploy. Original code is otherwise unchanged.

## What was missing / fixed (Muse's additions, 2026-11-26)

- `database/schema.sql` — NEW. The original repo had no schema file at all.
  Tables inferred from the PHP queries: users, students, faculty, courses,
  enrollments, attendance, marks, fee_structure, fee_payments, backup_log.
  Canonical attendance date column is `attendance_date`.
- `includes/db.php` — now reads DB from env vars (`DB_HOST`, `DB_PORT`,
  `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL`) or `DATABASE_URL`, with
  TiDB TLS support. Local fallback is still localhost/root.
- `setup.php` — NEW, one-time. Creates tables from `database/schema.sql`
  and seeds demo accounts + 1 course / student / faculty. Open `/setup.php`
  once after deploy, then DELETE it.
- `index.php` — NEW. Redirects `/` to `login.php` (there was no index).
- `vercel.json` + `api/index.php` — NEW. Vercel PHP router
  (`vercel-php@0.9.0`): every request goes to the matching page in the
  project root, same layout as XAMPP.
- `faculty/faculty_view_attendance.php`, `faculty/faculty_view_marks.php` — NEW.
  The faculty dashboard linked to them, but they did not exist.
- Bug fixes:
  - `att_date` → `attendance_date` in student attendance + admin dashboard.
  - `faculty_add_marks.php`: INSERT had 6 columns but only 3 `?` placeholders.
  - `fee_collection.php`: `ORDER BY fp.id` → `fp.payment_id`.
  - `admin_add_marks.php` / `admin_mark_attendance.php`: redirects to
    `add_marks.php` / `mark_attendance.php` → correct self filenames.
  - All `href="dashboard.php"` → `admin_dashboard.php`
    (accountant fee page → `accountant_dashboard.php`).
  - `faculty_mark_attendance.php`: `CALL mark_attendance_bulk(...)` stored
    procedure did not exist — replaced with a PHP loop inserting Absent rows.
  - `admin/backup.php`: `exec(mysqldump)` cannot run on Vercel — now a
    log-only page; backups come from TiDB Cloud.
  - Student pages now resolve the student record by `user_id`
    (helper `erp_student_id()` in `db.php`); `register.php` creates a linked
    student/faculty profile so a new account can log in and see its dashboard.

## Deploy

1. Push this repo to GitHub, import it in Vercel.
2. Database: TiDB Cloud Starter (free, MySQL-compatible) via Vercel
   Marketplace / env vars — Vercel's own Neon database is Postgres and
   cannot serve this PHP/MySQL code. Set in Vercel:
   `DB_HOST`, `DB_PORT=4000`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL=1`.
   Create the database first in TiDB Cloud (e.g. `university_erp`).
3. Deploy, open `/setup.php` once, delete `setup.php`, open `/login.php`.

## Demo logins (created by setup.php)

- Admin: `admin` / `admin123`
- Faculty: `faculty1` / `faculty123`
- Student: `student1` / `student123`
- Accountant: `accountant1` / `account123`

## Local test (2026-11-26, before Vercel)

PHP 8.3 + MariaDB 10.11, built-in server. All passed:
- `php -l` clean on all 29 PHP files.
- `/setup.php`: 11 schema statements ok, demo users + course/seed created.
- Login as all 4 roles works (admin, faculty1, student1, accountant1);
  student attendance + marks, faculty view pages, admin manage students
  + fee report all load. Wrong password shows "Galat password".
- Vercel router (`api/index.php`) tested with a local rewrite simulator:
  `/`, `/login.php`, `/admin/admin_dashboard.php` all work, unknown page 404s.
- One bug caught by the test and fixed: `setup.php` skipped the first
  CREATE TABLE (header comment), and a `session.save_path` override in
  `db.php` made the login session invisible to dashboards.

## Known limits (honest)

- Vercel PHP is a community serverless runtime, not a normal PHP host.
  PHP sessions are file-based in `/tmp` and may not survive across
  serverless invocations — if login drops between pages, that is the cause.
  Apkora (same stack) had the same Vercel limitation.
- File uploads / `backups/` do not persist on Vercel (ephemeral filesystem).
