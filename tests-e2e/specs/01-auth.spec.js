// Section 2: Admin authentication & session.
// Runs WITHOUT the stored session (fresh context) so it can exercise login/logout.
const { test, expect } = require('@playwright/test');
const { CREDS, ADMIN } = require('../helpers/data');
const { login, logout, isOnLogin } = require('../helpers/actions');

test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Admin authentication', () => {
  test('login page loads with CSRF token and no plaintext password', async ({ page }) => {
    await page.goto(ADMIN.login);
    await expect(page.locator('form input[name="csrf_token"]')).toHaveCount(1);
    await expect(page.locator('#password')).toHaveAttribute('type', 'password');
    // Password value must never be pre-filled.
    await expect(page.locator('#password')).toHaveValue('');
  });

  test('valid login reaches the dashboard', async ({ page }) => {
    await login(page);
    await page.goto(ADMIN.dashboard);
    expect(await isOnLogin(page)).toBeFalsy();
  });

  test('invalid password is rejected with a generic error', async ({ page }) => {
    await login(page, CREDS.email, 'WrongPassword_123');
    expect(await isOnLogin(page)).toBeTruthy();
    const body = await page.locator('body').innerText();
    // Error must NOT reveal whether the account exists.
    expect(body).toMatch(/invalid email or password/i);
    expect(body).not.toMatch(/no account|user not found|unknown email/i);
  });

  test('unknown email yields the same generic error (no user enumeration)', async ({ page }) => {
    await login(page, 'TEST_ADMIN_nobody@example.com', 'whatever_123');
    expect(await isOnLogin(page)).toBeTruthy();
    await expect(page.locator('body')).toContainText(/invalid email or password/i);
  });

  test('empty fields do not authenticate', async ({ page }) => {
    await page.goto(ADMIN.login);
    await page.click('button[type="submit"]');
    // HTML5 "required" keeps us on the login page.
    expect(await isOnLogin(page)).toBeTruthy();
  });

  test('malformed email does not authenticate', async ({ page }) => {
    await login(page, 'not-an-email', 'whatever_123');
    expect(await isOnLogin(page)).toBeTruthy();
  });

  test('no credentials appear in the URL after submit', async ({ page }) => {
    await login(page);
    expect(page.url()).not.toMatch(/password|passwd|pwd/i);
  });

  const protectedPages = [
    ADMIN.dashboard, ADMIN.businessInfo, ADMIN.homepage, ADMIN.about,
    ADMIN.services, ADMIN.gallery, ADMIN.faqs, ADMIN.quotes,
    ADMIN.messages, ADMIN.seo, ADMIN.settings,
  ];
  for (const url of protectedPages) {
    test(`protected page requires auth: ${url}`, async ({ page }) => {
      await page.goto(url);
      // Should be redirected to login (or show the login form), not the admin content.
      expect(await isOnLogin(page)).toBeTruthy();
    });
  }

  test('session persists across navigation', async ({ page }) => {
    await login(page);
    await page.goto(ADMIN.services);
    expect(await isOnLogin(page)).toBeFalsy();
    await page.goto(ADMIN.gallery);
    expect(await isOnLogin(page)).toBeFalsy();
  });

  test('logout ends the session and blocks protected pages', async ({ page }) => {
    await login(page);
    await logout(page);
    await page.goto(ADMIN.dashboard);
    expect(await isOnLogin(page)).toBeTruthy();
  });

  test('browser Back after logout does not expose admin content', async ({ page }) => {
    await login(page);
    await page.goto(ADMIN.dashboard);
    await logout(page);
    await page.goBack();
    // Even from cache, re-requesting a protected page must redirect to login.
    await page.reload();
    expect(await isOnLogin(page)).toBeTruthy();
  });

  test('session id regenerates on login (fixation protection)', async ({ page, context }) => {
    await page.goto(ADMIN.login);
    const before = (await context.cookies()).find((c) => /^phpsessid$/i.test(c.name));
    await login(page);
    const after = (await context.cookies()).find((c) => /^phpsessid$/i.test(c.name));
    // A pre-login session cookie, if present, must change after a successful login.
    if (before && after) {
      expect(after.value).not.toBe(before.value);
    }
  });

  test('login rejects a request with a bad CSRF token', async ({ page, request }) => {
    await page.goto(ADMIN.login);
    const res = await request.post(ADMIN.login, {
      form: { email: CREDS.email, password: CREDS.password, csrf_token: 'bogus-token' },
      maxRedirects: 0,
    });
    // A CSRF failure must NOT establish a session (no redirect to dashboard).
    const loc = res.headers()['location'] || '';
    expect(loc).not.toMatch(/dashboard/);
  });
});
