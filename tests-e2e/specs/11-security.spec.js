// Section 16: Security checks (safe, non-destructive local payloads).
const { test, expect } = require('@playwright/test');
const { ADMIN } = require('../helpers/data');

test.describe('Security', () => {
  test('write action without a CSRF token is rejected', async ({ page }) => {
    // POST to business-info without csrf_token; must not report success.
    const res = await page.request.post(ADMIN.businessInfo, {
      form: { business_name: 'TEST_ADMIN_nocsrf', tagline: 'x' },
      maxRedirects: 0,
    });
    expect(res.status()).toBeLessThan(500);
    const body = await res.text().catch(() => '');
    expect(body).not.toMatch(/settings saved|success/i);
  });

  test('destructive action over GET does not delete (id ignored without proper POST/CSRF)', async ({ page }) => {
    // Hitting a delete endpoint via GET on a non-existent id must not 500 or wipe data.
    const res = await page.request.get(`${ADMIN.services}?action=delete&id=99999999`, { maxRedirects: 0 });
    expect(res.status()).toBeLessThan(500);
    expect(await res.text().catch(() => '')).not.toMatch(/SQLSTATE|Fatal error/);
  });

  test('SQL-injection string in a search/param does not error the DB', async ({ page }) => {
    const inj = encodeURIComponent("1' OR '1'='1");
    const res = await page.request.get(`${ADMIN.quotes}?id=${inj}`);
    expect(res.status()).toBeLessThan(500);
    expect(await res.text()).not.toMatch(/SQLSTATE|You have an error in your SQL syntax/);
  });

  test('path traversal on an admin id param is not reflected as a file read', async ({ page }) => {
    const trav = encodeURIComponent('../../includes/config.php');
    const res = await page.request.get(`${ADMIN.quotes}?id=${trav}`);
    expect(res.status()).toBeLessThan(500);
    expect(await res.text()).not.toMatch(/DB_PASS|DB_USER|define\(/);
  });

  test('uploaded files in /uploads cannot execute as PHP', async ({ page }) => {
    // A .php placed under uploads (if any) must not execute; probe a guessable path.
    const res = await page.request.get('/uploads/test_admin_probe.php');
    // 404 is ideal; if 200, the body must NOT be executed PHP output.
    if (res.status() === 200) {
      expect(await res.text()).not.toMatch(/pwned/);
    } else {
      expect(res.status()).toBeGreaterThanOrEqual(400);
    }
  });

  test('private config / sensitive files are not directly served', async ({ page }) => {
    for (const path of [
      '/includes/config.php',
      '/includes/db.php',
      '/.env',
      '/database/schema.sql',
    ]) {
      const res = await page.request.get(path);
      const body = await res.text().catch(() => '');
      // PHP files should be executed (empty/no source) or denied - never leak source.
      expect(body, `${path} leaked source`).not.toMatch(/DB_PASS|DB_USER|password_hash|define\('DB_/);
    }
  });
});
