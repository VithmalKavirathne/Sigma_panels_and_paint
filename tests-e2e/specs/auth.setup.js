// Authenticates once and saves the admin session for the desktop/mobile projects.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const { CREDS, ADMIN } = require('../helpers/data');

const AUTH_FILE = '.auth/admin.json';

test('authenticate admin session', async ({ page }) => {
  expect(CREDS.password, 'ADMIN_PASSWORD must be set in tests-e2e/.env').not.toBe('');

  await page.goto(ADMIN.login);
  await page.fill('#email', CREDS.email);
  await page.fill('#password', CREDS.password);
  await Promise.all([
    page.waitForURL(/\/admin\/(dashboard)?/, { timeout: 15000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);

  // Confirm we are authenticated (dashboard reachable, no login form).
  await page.goto(ADMIN.dashboard);
  await expect(page.locator('input#password')).toHaveCount(0);

  if (!fs.existsSync('.auth')) fs.mkdirSync('.auth', { recursive: true });
  await page.context().storageState({ path: AUTH_FILE });
});
