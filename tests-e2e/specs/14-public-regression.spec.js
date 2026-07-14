// Section 21: Public regression. Runs in BOTH desktop and mobile projects.
// Uses a fresh (unauthenticated) context so it reflects a normal visitor.
const { test, expect } = require('@playwright/test');
const { PUBLIC, attachConsoleGuard } = require('../helpers/data');

test.use({ storageState: { cookies: [], origins: [] } });

const pages = [
  ['home', PUBLIC.home],
  ['about', PUBLIC.about],
  ['services', PUBLIC.services],
  ['gallery', PUBLIC.gallery],
  ['quote', PUBLIC.quote],
  ['contact', PUBLIC.contact],
  ['faq', PUBLIC.faq],
  ['privacy', PUBLIC.privacy],
  ['terms', PUBLIC.terms],
];

test.describe('Public pages', () => {
  for (const [label, url] of pages) {
    test(`${label} loads cleanly with no console errors`, async ({ page }) => {
      const errors = attachConsoleGuard(page);
      const resp = await page.goto(url, { waitUntil: 'load' });
      expect(resp && resp.status(), `${label} status`).toBeLessThan(400);
      const text = await page.locator('body').innerText();
      expect(text).not.toMatch(/(Warning|Notice|Fatal error|Parse error):/);
      // Allow a short beat for GSAP/Lenis init.
      await page.waitForTimeout(500);
      expect(errors, `console errors on ${label}`).toEqual([]);
      const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
      );
      expect(overflow, `horizontal overflow on ${label}`).toBeLessThanOrEqual(2);
    });
  }

  test('sitemap.xml and robots.txt are served', async ({ page }) => {
    expect((await page.request.get(PUBLIC.sitemap)).status()).toBe(200);
    expect((await page.request.get(PUBLIC.robots)).status()).toBe(200);
  });

  test('homepage hero + booth init without throwing', async ({ page }) => {
    const errors = attachConsoleGuard(page);
    await page.goto(PUBLIC.home, { waitUntil: 'load' });
    await page.waitForTimeout(800);
    // Hero paint-reveal element exists and the page didn't error.
    expect(await page.locator('.paint-reveal, #hero').count()).toBeGreaterThan(0);
    expect(errors).toEqual([]);
  });
});
