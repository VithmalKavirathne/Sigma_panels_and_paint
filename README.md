# Sigma Panels & Paint - Hostinger Git Deployment

This repository **is** the `public_html` web root. When Hostinger's Git
integration pulls this repo, its contents become the live site root.

Real database credentials are **never** stored in this repo. They live in a
private file **outside** `public_html`.

## Layout on the server

```
domains/your-domain/
├── public_html/          <-- this Git repo is deployed here
│   ├── index.php         (serves the homepage)
│   ├── .htaccess         (clean URLs + security)
│   ├── public/           (public pages)
│   ├── admin/            (admin panel)
│   ├── assets/           (css, js, stock images)
│   ├── includes/
│   │   └── config.php    (loader - NO secrets, safe in Git)
│   └── uploads/          (writable, empty in Git except .gitkeep)
└── sigma_private/        <-- OUTSIDE public_html, NOT in Git
    └── config.php        (real DB credentials)
```

`includes/config.php` loads `../sigma_private/config.php` (one level above
`public_html`). If that file is missing the site returns a clear 500 message
instead of exposing anything.

## First-time deployment

1. **Create the database** in Hostinger (hPanel > Databases > MySQL). Note the
   database name, user, and password.

2. **Import the schema** via phpMyAdmin, in order:
   `database/schema.sql`, then `database/seed.sql`, then any
   `database/migrations/*.sql`.
   (These SQL files live in your source project, not in this deploy repo.)

3. **Create the private config** one level above `public_html`:
   `domains/your-domain/sigma_private/config.php`
   Use `includes/config.example.php` as the template and fill in the real
   Hostinger DB credentials and your `https://` domain (no trailing slash).

4. **Connect Git** in hPanel > Advanced > Git, pointing the repository at
   `public_html`, then Deploy. Every push can then be pulled with one click.

5. **Permissions**: ensure `uploads/` and its subfolders are writable
   (folders 755, files 644).

6. **Admin login** (default seed): `admin@sigmapanels.com.au` /
   `SigmaAdmin2026!` - change this immediately after first login.

## Smoke test after deploy

- `https://your-domain/`
- `https://your-domain/admin/login`
- `https://your-domain/sitemap.xml`
- `https://your-domain/robots.txt`

Submit one test quote + contact message, confirm they appear in the admin,
then delete them.

## What is intentionally NOT in this repo

Tests, `database/`, `docs/`, `references/`, `screenshots/`, Playwright reports,
`node_modules/`, `stitch_*`, `public/db-test.php`, local `.ps1`/`.bat` scripts,
real DB config, and uploaded images. Upload folders ship empty (only
`.gitkeep`). Stock SVGs under `assets/images` are kept.
