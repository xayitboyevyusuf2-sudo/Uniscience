# UniScience.uz — Laravel 11 MVP (application layer)

This folder is an OVERLAY: copy it over a fresh Laravel 11 project.

    composer create-project laravel/laravel uniscience && cd uniscience
    # copy the contents of this folder over the project (merge; replace routes/web.php and app/Models/User.php)
    composer require simplesoftwareio/simple-qrcode     # optional: QR on the certificate page
    # .env: DB_CONNECTION=mysql, DB_DATABASE=..., DB_USERNAME=..., DB_PASSWORD=..., APP_URL=https://your-domain
    php artisan migrate --seed          # admin@uniscience.test / change-me-123  -> CHANGE IT
    php artisan serve

Tests: in phpunit.xml uncomment `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` FIRST (RefreshDatabase wipes the DB), then `php artisan test`.

Not included yet: password reset by email (FR-03), email notifications, CSV/PDF exports (FR-26/27), group rating, CSV import of students (FR-04), moderator-only UI beyond the queue.
Seeded journals are SAMPLES. Import the real OAK list in Admin > CSV. Tailwind uses the CDN; compile with Vite for production.

## Update 1: compiled Tailwind, password reset, notification emails
    npm install && npm run build      # production CSS (use `npm run dev` while developing; rerun build after view changes)
Local mail: default MAIL_MAILER=log writes emails to storage/logs/laravel.log. For real mail set in .env:
MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME.

## Update 2: new look, separate first/last name
    php artisan migrate        # adds first_name/last_name; existing users are split at the first space (no data lost)
    npm run build

## Update 5: journals without ISSN (real OAK list)
1. Add to your local `.env`:  ADMIN_EMAIL=you@example.com  and  ADMIN_PASSWORD=a-long-private-password
2. `php artisan migrate:fresh --seed`   (WIPES local test data; imports the OAK list from database/data/oak_import.csv and creates the admin from .env)
3. `npm run build`, then `php artisan test`.
If you copied update 4, delete database/migrations/2026_10_04_000001_seed_initial_data.php first.
Matching: ISSN if given and known, else the journal name (case/punctuation ignored); anything unclear goes to the moderator, who picks the journal. The ISSN students enter is saved on the journal when a moderator approves.

## Update 6: student profile + optional photo
Adds /profil (edit) and /talaba/{id} (view), visible to logged-in users only. Photos are stored on the private disk and served through /foto/{id}.
Run `php artisan migrate:fresh --seed` once after copying update 5 and update 6 (see Update 5), then `npm run build` and `php artisan test`.
