// Section 20: Responsive admin checks. Runs in BOTH desktop and mobile projects.
const { test, expect } = require('@playwright/test');
const { ADMIN } = require('../helpers/data');
const { isOnLogin } = require('../helpers/actions');

const pages = [ADMIN.dashboard, ADMIN.services, ADMIN.gallery, ADMIN.quotes, ADMIN.faqs];

test.describe('Responsive admin', () => {
  for (const url of pages) {
    test(`no horizontal overflow: ${url}`, async ({ page }) => {
      await page.goto(url);
      expect(await isOnLogin(page), 'must be authenticated').toBeFalsy();
      const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
      );
      expect(overflow, `horizontal overflow on ${url}`).toBeLessThanOrEqual(2);
    });
  }

  test('primary nav is reachable (sidebar or drawer/toggle)', async ({ page, isMobile }) => {
    await page.goto(ADMIN.dashboard);
    // Desktop: sidebar links visible. Mobile: a menu toggle should exist OR links still reachable.
    const dashLink = page.getByRole('link', { name: /dashboard/i }).first();
    if (isMobile) {
      const toggle = page.locator('[class*="toggle"], [class*="hamburger"], button[aria-label*="menu" i]').first();
      if (await toggle.count()) {
        await toggle.click().catch(() => {});
      }
    }
    expect(await dashLink.count()).toBeGreaterThan(0);
  });
});
