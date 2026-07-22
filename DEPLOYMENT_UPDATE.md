# Deployment Update Guide (CyberPanel, no Docker)

This is an **update / redeploy** runbook for an existing CyberPanel deployment —
code sync + DB migrations + cache rebuild. It is **not** a fresh install.

> Docker is only used for local development. Production is a plain CyberPanel /
> OpenLiteSpeed + MySQL host. Nothing here requires Docker.

**Stack:** PHP 8.2, Laravel 11, MySQL. `database` queue/cache/sessions.
Timezone `Asia/Kolkata`. Media/download files on the `public` disk.

Run everything below over **SSH, as the site user**, from the Laravel root
(e.g. `/home/<yourdomain>/public_html`).

---

## 0. Before you touch anything — back up

One migration **de-duplicates rows and adds a unique index**, so a DB backup is required.

```bash
# DB backup (REQUIRED)
mysqldump -u <db_user> -p <db_name> > ~/backup_$(date +%F_%H%M).sql

# quick code backup of what we touch
tar czf ~/code_backup_$(date +%F).tgz app plugins config resources public/assets
```

---

## 1. Maintenance mode

```bash
php artisan down
```

---

## 2. Get the new code onto the server

**If you deploy via git:** `git pull` (or your usual flow).

**If you upload files** (CyberPanel File Manager / SFTP): replace the files below.
**Do NOT touch** `.env`, `storage/`, `vendor/`, `node_modules/`, or the
`public/storage` symlink.

### New files (must be added)
- `config/timetracker.php`
- `app/Console/Commands/AutoCloseOpenShifts.php`
- `database/seeders/CandidateStatusSeeder.php`
- `plugins/TimeTracker/Concerns/HandlesTimeTrackerLogs.php`
- `plugins/TimeTracker/Database/Migrations/2026_07_22_000001_add_reason_and_unique_index_to_time_tracker_activity_logs.php`
- `plugins/TimeTracker/Database/Migrations/2026_07_22_000002_relax_app_downloads_for_general_files.php`
- `plugins/TimeTracker/Database/Migrations/2026_07_22_000003_add_download_count_to_app_downloads.php`

### Modified files (overwrite)
- `app/Console/Kernel.php`
- `app/Http/Controllers/CandidateController.php`
- `database/seeders/DatabaseSeeder.php`
- `plugins/TimeTracker/Controllers/TimeTrackerController.php`
- `plugins/TimeTracker/Controllers/TimeAndAttendanceController.php`
- `plugins/TimeTracker/Controllers/ManualTimeController.php`
- `plugins/TimeTracker/Controllers/DashboardController.php`
- `plugins/TimeTracker/Controllers/AppDownloadController.php`
- `plugins/TimeTracker/Models/TimeTrackerActivityLog.php`
- `plugins/TimeTracker/Models/AppDownload.php`
- `plugins/TimeTracker/Resources/views/downloads/index.blade.php`
- `plugins/TimeTracker/Resources/views/downloads/upload.blade.php`
- `resources/views/modals.blade.php`
- `public/assets/css/custom.css`
- `public/assets/js/custom.js`

> **Unsure which files diverged?** Safest option is to sync the **whole project**
> except `.env`, `storage/`, `vendor/`, `node_modules/`, and `public/storage`.

---

## 3. Fix ownership (only if you uploaded as root)

```bash
chown -R <siteuser>:<siteuser> .
chmod -R 775 storage bootstrap/cache
```

On CyberPanel `<siteuser>` is usually the domain's user.

---

## 4. Migrations + seeder

```bash
php artisan migrate --force
php artisan db:seed --class=CandidateStatusSeeder --force   # default candidate statuses (idempotent)
```

What the migrations do:
- Add `reason` column + `UNIQUE(user_id, action, timestamp)` on `time_tracker_activity_logs`
  (de-dupes existing duplicate rows first).
- Relax `app_downloads`: `title` added; `platform` / `version` / `file_type` made optional.
- Add `download_count` to `app_downloads`.

---

## 5. Storage symlink

For the Downloads public links and candidate resume files:

```bash
php artisan storage:link   # harmless if it already exists
```

If `/storage/...` URLs 404 afterwards, enable **Follow Symbolic Link** on the vHost
(OpenLiteSpeed) and restart LiteSpeed.

---

## 6. Rebuild caches (critical — production caches config)

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Bring it back up

```bash
php artisan up
```

---

## 8. Verify background processes

**Scheduler cron** (needed for the nightly auto-close of open shifts). Confirm this
line is in the site user's `crontab -l`; add it if missing:

```
* * * * * cd /home/<yourdomain>/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** — only if you already run one (`QUEUE_CONNECTION=database`). Restart
it so it loads the new code:

```bash
systemctl restart <your-worker>     # or: supervisorctl restart all
```

---

## Notes specific to this update

- **No `composer install` / `npm build` needed** — no PHP packages were added, and the
  CSS/JS changes are static files in `public/assets` (not Vite-built).
- **Browser / LiteSpeed cache:** `public/assets/js/custom.js` has no version string,
  so **purge the LiteSpeed cache** in CyberPanel and hard-refresh (Ctrl+F5) once after
  deploy, or users may keep the old JS (e.g. the table action-dropdown fix). `custom.css`
  already cache-busts itself.
- **Screenshot force-clockout kill-switch:** if the tracker ever misfires, set
  `TIMETRACKER_SCREENSHOT_GATE=false` in `.env`, then run `php artisan config:cache`.
  (Config is cached in production, so the toggle lives in `config/timetracker.php` and
  only applies after re-caching.)
- **PHP upload limits** (Downloads / resume uploads): CyberPanel → PHP → Edit PHP Config
  for your version — set `upload_max_filesize` and `post_max_size` ≥ the largest file you
  will upload (app cap is 500 MB). LiteSpeed's request-body limit must be ≥ that too.
- **Timezone:** the tracker interprets punches in the workspace timezone from
  *General Settings* (`Asia/Kolkata`). Keep that correct; timestamps are stored in UTC.

---

## Rollback

```bash
php artisan down
mysql -u <db_user> -p <db_name> < ~/backup_<date>.sql       # restore DB
# restore code from ~/code_backup_<date>.tgz
php artisan optimize:clear
php artisan up
```

---

## One-shot (steps 4–7)

After the code is in place (step 2) and ownership is correct (step 3):

```bash
php artisan migrate --force \
  && php artisan db:seed --class=CandidateStatusSeeder --force \
  && php artisan storage:link \
  && php artisan optimize:clear \
  && php artisan config:cache \
  && php artisan route:cache \
  && php artisan view:cache \
  && php artisan up
```
