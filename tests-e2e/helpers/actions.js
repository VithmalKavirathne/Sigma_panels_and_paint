// Reusable admin actions for the E2E suite.
const { expect } = require('@playwright/test');
const { CREDS, ADMIN } = require('./data');

// Log in through the real login form (includes CSRF field automatically).
async function login(page, email = CREDS.email, password = CREDS.password) {
  await page.goto(ADMIN.login);
  await page.fill('#email', email);
  await page.fill('#password', password);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]'),
  ]);
}

async function logout(page) {
  await page.goto(ADMIN.logout);
  await page.waitForLoadState('networkidle');
}

// True if the current page is (or redirected to) the login screen.
async function isOnLogin(page) {
  return /\/admin\/login/.test(page.url()) || (await page.locator('input#password').count()) > 0;
}

// Delete every admin list row whose text contains the given token, by clicking
// the row's "Delete" button and accepting the confirm dialog. Used for cleanup.
async function deleteRowsContaining(page, listUrl, token) {
  await page.goto(listUrl);
  // Rows are <tr> (or cards) containing a Delete submit button in a form.
  let guard = 0;
  while (guard++ < 20) {
    const row = page.locator('tr, .admin-card, li').filter({ hasText: token }).first();
    if ((await row.count()) === 0) break;
    const del = row.getByRole('button', { name: /delete/i });
    if ((await del.count()) === 0) break;
    page.once('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      del.first().click(),
    ]);
  }
}

module.exports = { login, logout, isOnLogin, deleteRowsContaining };
