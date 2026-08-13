# Repair Workshop Self-Service Kiosk — v1 (tested, runnable)

Self-service drop-off and collection terminal for an equipment repair
workshop. Barcode/QR scan in, QR tracking slip out.

## What's included in v1
- Kiosk: full drop-off wizard (scan → identify → fault → confirm → QR slip)
- Kiosk: full collection wizard (scan → verify → collect → receipt)
- Public tracking page at /track/{token} — no login required
- Staff panel: login, dashboard, job list, job detail, status changes
- The job status state machine (app/Config/statuses.php) is enforced
  server-side in app/Services/JobService.php — illegal transitions are
  rejected even if someone tampers with the form
- Print-ready drop-off slip and handover receipt (browser print dialog)
- **No Composer required to run v1.** A tiny built-in .env loader and
  autoloader replace vlucas/phpdotenv for now. QR codes are generated
  via a free external API (api.qrserver.com) rather than a local
  library — this requires internet access from wherever the app runs.
  See app/Services/QrService.php for the offline upgrade path.

## Not yet built (planned next)
- Equipment / borrower / staff-user CRUD screens (register them
  directly in the database for now — see database/schema.sql seed data)
- Photo upload on drop-off
- Signature capture on collection
- Accessories checklist
- Reports / CSV export
- Role-based restrictions beyond "logged in or not"

## Setup (WAMP / XAMPP)
1. Copy this whole folder to your web server's document root, e.g.
   `C:\wamp64\www\repair-kiosk`
2. Import the database:
   `mysql -u root -p < database/schema.sql`
   (or phpMyAdmin → Import, with no database pre-selected)
3. Edit `.env` — set DB_USER / DB_PASS to match your MySQL install,
   and set APP_URL / TRACK_BASE_URL to your actual URL. For the QR
   code to be scannable from a phone, TRACK_BASE_URL must be reachable
   from a phone on your network — not "localhost".
4. Point your web server's document root at the `public/` folder.
5. Visit `/kiosk` to test the drop-off/collection flow.
   Visit `/staff/login` to test the staff panel.

## Default login
Username: `admin`  Password: `Admin@123` — change this before real use.

## Tested
This exact codebase was run through a 26-step automated test covering
the full drop-off → track → staff status changes → collection loop,
including the security guards (duplicate drop-off blocked, illegal
status transition rejected, CSRF rejected, staff routes require login).
