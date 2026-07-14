// Section 4: Dashboard sanity (authenticated). We can't read the DB directly
// from the browser, so we assert the dashboard renders numeric stats, links to
// the right pages, and has no SQL/PHP errors - rather than hard-coding values.
const { test, expect } = require('@playwright/test');
const { ADMIN } = require('../helpers/data');

test.describe('Dashboard', () => {
  test('renders without SQL/PHP errors and shows stat values', async ({ page }) => {
    await page.goto(ADMIN.dashboard);
    const text = await page.locator('body').innerText();
    expect(text).not.toMatch(/SQLSTATE|Fatal error|Warning:|Notice:/);
    // Dashboard should surface at least one numeric count somewhere.
    expect(text).toMatch(/\d/);
  });

  test('dashboard links point at real admin pages (no 404)', async ({ page }) => {
    await page.goto(ADMIN.dashboard);
    const hrefs = await page.locator('a[href*="/admin/"]').evaluateAll(
      (as) => Array.from(new Set(as.map((a) => a.getAttribute('href')).filter(Boolean)))
    );
    // Probe a handful of internal admin links for non-error responses.
    for (const href of hrefs.slice(0, 12)) {
      const resp = await page.request.get(href);
      expect(resp.status(), `${href} status`).toBeLessThan(400);
    }
  });
});
