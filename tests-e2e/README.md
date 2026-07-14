# Sigma Panels & Paint — Admin E2E Test Suite (Playwright)

End-to-end tests for the admin panel and a public regression pass. They drive a
real browser against your **locally running** app, create only `TEST_ADMIN_`
records, and clean up after themselves.

> These tests must run on **your machine**, where the app and database are
> reachable. They were authored from the code but have **not** been executed in
> this environment (no local PHP/MySQL/browser was reachable here).

## 1. Prerequisites

- The app running at `http://localhost:8088/` (public) and `/admin/login` (admin).
- MySQL/MariaDB on port **3307**, database `sigma_web_latest`, reachable by the app.
- Node.js 18+.
- **Back up the database before the first run** (the suite writes/deletes test rows):
  ```
  mysqldump -h 127.0.0.1 -P 3307 -u root sigma_web_latest > backup_before_e2e.sql
  ```

## 2. Install

```
cd tests-e2e
npm install
npm run install:browsers   # downloads Chromium for Playwright
```

## 3. Configure credentials

Credentials come from `tests-e2e/.env` (gitignored — never commit it):

```
BASE_URL=http://localhost:8088
ADMIN_EMAIL=admin@sigmapanels.com.au
ADMIN_PASSWORD=********   # your local dev password
```

A ready `.env` is included for local convenience; edit it if your password
differs. `.env.example` is the shareable template.

## 4. Run

```
npm test                 # full suite (desktop + mobile projects)
npm run test:desktop     # desktop project only
npm run test:mobile      # responsive + public regression at mobile sizes
npm run report           # open the HTML report from the last run
```

Failures produce screenshots, video, and traces under `test-results/` and the
HTML report under `playwright-report/`.

## 5. What’s covered

| Spec | Area |
|------|------|
| `auth.setup.js` | Logs in once, stores the admin session for reuse |
| `01-auth` | Login valid/invalid/unknown/empty/malformed, protected-route redirect, session persistence, logout, back-after-logout, session regeneration, CSRF-rejected login, no user enumeration, no password in URL |
| `02-navigation` | Every admin page loads (no 404/blank/PHP warning/console error), refresh works, nav present, no overflow |
| `03-dashboard` | Renders stats, no SQL/PHP errors, internal links resolve |
| `04-business-info` | Save + public reflection + restore, required-field guard, stored-XSS escape |
| `05-services-crud` | Create (with image) → admin+public visibility → duplicate-slug + missing-title rejection → confirm-delete → gone |
| `06-gallery-crud` | Create with valid image → public gallery → delete |
| `07-faq-crud` | Create/publish → public `/faq` + escaping → empty-question guard → delete |
| `08-seo` | Global SEO save + restore, head-injection guard, sitemap/robots valid |
| `09-quote` | Public submit → admin visibility → internal note persistence → bad-id safety → confirm-delete |
| `10-contact` | Public submit → admin visibility + body escaping → bad-id safety → delete |
| `11-security` | No-CSRF write rejected, GET-delete safe, SQLi/param, path traversal, uploads not executable, config not leaked |
| `12-uploads` | PHP-as-jpg / raw-php / text-as-jpg / oversized rejection, no directory listing |
| `13-responsive` | No overflow + reachable nav (desktop + mobile) |
| `14-public-regression` | All public pages load clean, no console errors, sitemap/robots, hero/booth init (desktop + mobile) |
| `15-homepage` | Editor renders; visibility toggle round-trips (non-destructive) |
| `16-about` | Create → `/about` reflection + XSS guard → delete |

## 6. Cleanup & safety

- Every create spec deletes its `TEST_ADMIN_*` rows in `afterAll`.
- If a run is interrupted, remove leftovers manually — they’re all prefixed
  `TEST_ADMIN_`:
  ```sql
  SELECT id,title FROM services       WHERE title LIKE 'TEST_ADMIN_%';
  SELECT id,title FROM gallery_items  WHERE title LIKE 'TEST_ADMIN_%';
  SELECT id,question FROM faqs         WHERE question LIKE 'TEST_ADMIN_%';
  SELECT id FROM quote_requests        WHERE customer_name LIKE 'TEST_ADMIN_%';
  SELECT id FROM messages              WHERE name LIKE 'TEST_ADMIN_%';
  SELECT id,title FROM homepage_sections WHERE title LIKE 'TEST_ADMIN_%';
  -- delete the matching rows, then remove any TEST_ADMIN_ files under /uploads/*
  ```
- The suite **restores** business-info and SEO values it changes. Homepage is
  never mutated destructively.

## 7. Known selector assumptions

Selectors were derived from the current admin markup (e.g. `#email`,
`input[name="title"]`, buttons `Save Service` / `Save Item` / `Save FAQ` /
`Save Settings`, delete buttons inside each list row, and `?action=delete&id=`
routes). If a template changes those names, update the matching spec. Specs use
`test.skip(...)` where a control (e.g. a quote detail link or note field) may not
exist, so they degrade gracefully rather than false-failing.
