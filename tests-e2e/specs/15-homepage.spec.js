// Section 6: Homepage manager. Homepage sections support toggle (no delete),
// so this spec is intentionally non-destructive: it verifies the editor renders
// and that a visibility toggle round-trips without error.
const { test, expect } = require('@playwright/test');
const { ADMIN } = require('../helpers/data');

test.describe('Homepage manager', () => {
  test('editor renders its fields with no PHP error', async ({ page }) => {
    await page.goto(ADMIN.homepage);
    const body = await page.locator('body').innerText();
    expect(body).not.toMatch(/Fatal error|Warning:|Notice:|SQLSTATE/);
    await expect(page.locator('[name="title"]')).toHaveCount(1);
    await expect(page.getByRole('button', { name: /save section/i })).toHaveCount(1);
  });

  test('a section visibility toggle round-trips (toggle then restore)', async ({ page }) => {
    await page.goto(ADMIN.homepage);
    const toggle = page.getByRole('button', { name: /toggle/i }).first();
    test.skip((await toggle.count()) === 0, 'no section toggle available');

    // Toggle off/on and back to the original state (2 clicks = no net change).
    for (let i = 0; i < 2; i++) {
      const t = page.getByRole('button', { name: /toggle/i }).first();
      await Promise.all([page.waitForLoadState('networkidle'), t.click()]);
      expect(await page.locator('body').innerText()).not.toMatch(/Fatal error|SQLSTATE/);
    }
  });
});
