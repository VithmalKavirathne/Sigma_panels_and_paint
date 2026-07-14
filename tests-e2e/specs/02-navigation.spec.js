// Section 3: Admin navigation & layout (desktop, authenticated).
const { test, expect } = require('@playwright/test');
const { ADMIN } = require('../helpers/data');
const { attachConsoleGuard } = require('../helpers/data');
const { isOnLogin } = require('../helpers/actions');

const pages = [
  ['Dashboard', ADMIN.dashboard],
  ['Business Info', ADMIN.businessInfo],
  ['Homepage', ADMIN.homepage],
  ['About', ADMIN.about],
  ['Services', ADMIN.services],
  ['Gallery', ADMIN.gallery],
  ['FAQs', ADMIN.faqs],
  ['Quote Requests', ADMIN.quotes],
  ['Messages', ADMIN.messages],
  ['SEO', ADMIN.seo],
  ['Settings', ADMIN.settings],
];

test.describe('Admin navigation', () => {
  for (const [label, url] of pages) {
    test(`opens ${label} without errors`, async ({ page }) => {
      const errors = attachConsoleGuard(page);
      const resp = await page.goto(url);
      expect(resp && resp.status(), `${url} HTTP status`).toBeLessThan(400);
      expect(await isOnLogin(page), `${url} should be authenticated`).toBeFalsy();
      // No blank page: there should be visible body text.
      const text = (await page.locator('body').innerText()).trim();
      expect(text.length, `${url} should not be blank`).toBeGreaterThan(0);
      // No raw PHP warnings/notices leaking to the page.
      expect(text).not.toMatch(/(Warning|Notice|Fatal error|Parse error):/);
      expect(text).not.toMatch(/Uncaught|Stack trace|SQLSTATE/);
      expect(errors, `console errors on ${url}`).toEqual([]);
    });

    test(`browser refresh works on ${label}`, async ({ page }) => {
      await page.goto(url);
      await page.reload();
      expect(await isOnLogin(page)).toBeFalsy();
    });
  }

  test('the sidebar exposes each primary section link', async ({ page }) => {
    await page.goto(ADMIN.dashboard);
    for (const [label] of pages) {
      // At least one link/label per section is present in the chrome.
      const found = await page.getByText(new RegExp(`^${label}$`, 'i')).count();
      expect(found, `nav label "${label}"`).toBeGreaterThan(0);
    }
    await expect(page.getByText(/^logout$/i)).toHaveCount(1);
  });

  test('no horizontal overflow on the dashboard (desktop)', async ({ page }) => {
    await page.goto(ADMIN.dashboard);
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflow).toBeLessThanOrEqual(1);
  });
});
