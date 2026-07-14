// Section 12: SEO / global meta save + public <head> reflection, then restore.
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');

test.describe('SEO settings', () => {
  test('global SEO fields save without error', async ({ page }) => {
    await page.goto(ADMIN.seo);
    const gv = page.locator('[name="google_site_verification"]');
    test.skip((await gv.count()) === 0, 'global SEO form not present');

    const original = await gv.inputValue();
    const testValue = `${stamp()}googleverify`;
    await gv.fill(testValue);

    const saveGlobal = page.getByRole('button', { name: /save global seo/i });
    await Promise.all([
      page.waitForLoadState('networkidle'),
      saveGlobal.click(),
    ]);
    await expect(page.locator('body')).not.toContainText(/Fatal error|SQLSTATE|Warning:/);

    await page.reload();
    await expect(page.locator('[name="google_site_verification"]')).toHaveValue(testValue);

    // Restore.
    await page.locator('[name="google_site_verification"]').fill(original);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save global seo/i }).click(),
    ]);
  });

  test('a malformed verification value cannot inject markup into <head>', async ({ page }) => {
    await page.goto(ADMIN.seo);
    const gv = page.locator('[name="google_site_verification"]');
    test.skip((await gv.count()) === 0, 'global SEO form not present');
    const original = await gv.inputValue();
    const payload = `${stamp()}"><script>window.__seoxss=1</script>`;
    await gv.fill(payload);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save global seo/i }).click(),
    ]);
    await page.goto(PUBLIC.home);
    expect(await page.evaluate(() => window.__seoxss)).toBeUndefined();
    // Restore.
    await page.goto(ADMIN.seo);
    await page.locator('[name="google_site_verification"]').fill(original);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save global seo/i }).click(),
    ]);
  });

  test('sitemap.xml and robots.txt remain valid', async ({ page }) => {
    const sm = await page.request.get(PUBLIC.sitemap);
    expect(sm.status()).toBe(200);
    expect(await sm.text()).toMatch(/<urlset|<\?xml/);
    const rb = await page.request.get(PUBLIC.robots);
    expect(rb.status()).toBe(200);
    expect(await rb.text()).toMatch(/user-agent/i);
  });
});
