// Section 5: Business Information read/update, then RESTORE originals.
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');

test.describe('Business information', () => {
  test('update tagline, verify save + public reflection, then restore', async ({ page }) => {
    await page.goto(ADMIN.businessInfo);
    const tagline = page.locator('#tagline');
    await expect(tagline).toHaveCount(1);

    const original = await tagline.inputValue();
    const testValue = `${stamp()} tagline`;

    // Save the test value.
    await tagline.fill(testValue);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save settings/i }).click(),
    ]);
    await expect(page.locator('.alert-success, .alert')).toContainText(/saved|success|updated/i);

    // Persisted after refresh.
    await page.reload();
    await expect(page.locator('#tagline')).toHaveValue(testValue);

    // Reflected on a public page (footer uses the tagline).
    const home = await page.request.get(PUBLIC.home);
    expect(await home.text()).toContain(testValue);

    // Restore the original value.
    await page.goto(ADMIN.businessInfo);
    await page.locator('#tagline').fill(original);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save settings/i }).click(),
    ]);
    await page.reload();
    await expect(page.locator('#tagline')).toHaveValue(original);
  });

  test('required business name cannot be emptied', async ({ page }) => {
    await page.goto(ADMIN.businessInfo);
    const name = page.locator('#business_name');
    const original = await name.inputValue();
    await name.fill('');
    await page.getByRole('button', { name: /save settings/i }).click();
    // Either HTML5 required blocks submit, or server rejects: value must remain non-empty after reload.
    await page.goto(ADMIN.businessInfo);
    expect((await page.locator('#business_name').inputValue()).length).toBeGreaterThan(0);
    // (restore is implicit - original never changed)
    expect(original.length).toBeGreaterThan(0);
  });

  test('HTML in a text field is escaped on public output (no stored XSS)', async ({ page }) => {
    await page.goto(ADMIN.businessInfo);
    const tagline = page.locator('#tagline');
    const original = await tagline.inputValue();
    const payload = `${stamp()}<script>window.__xss=1</script>`;

    await tagline.fill(payload);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save settings/i }).click(),
    ]);

    // Visit public home and confirm the script did NOT execute.
    await page.goto(PUBLIC.home);
    expect(await page.evaluate(() => window.__xss)).toBeUndefined();

    // Restore.
    await page.goto(ADMIN.businessInfo);
    await page.locator('#tagline').fill(original);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save settings/i }).click(),
    ]);
  });
});
