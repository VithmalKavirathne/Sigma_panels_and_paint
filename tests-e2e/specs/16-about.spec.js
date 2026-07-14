// Section 7: About page manager. About sections support create + delete, so we
// do a full round-trip with a TEST_ADMIN_ section and clean it up.
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('About manager', () => {
  const token = stamp();
  const title = `${token}_About`;
  // Tricky characters + unicode + attempted script injection.
  const content = `${token} story with apostrophe's, ampersand &, "quotes", café, <script>window.__aboutxss=1</script>`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.about, token);
    await page.close();
  });

  test('create an about section', async ({ page }) => {
    await page.goto(ADMIN.about);
    await page.fill('[name="title"]', title);
    await page.fill('[name="content"]', content);
    if (await page.locator('[name="sort_order"]').count()) {
      await page.fill('[name="sort_order"]', '99');
    }
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save section/i }).click(),
    ]);
    await page.goto(ADMIN.about);
    await expect(page.locator('body')).toContainText(title);
  });

  test('about content shows on /about and the script does not execute', async ({ page }) => {
    await page.goto(PUBLIC.about, { waitUntil: 'load' });
    expect(await page.evaluate(() => window.__aboutxss)).toBeUndefined();
    expect(await page.locator('body').innerText()).toContain(token);
  });

  test('delete the about section', async ({ page }) => {
    await page.goto(ADMIN.about);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: title }).first();
    await expect(row).toHaveCount(1);
    page.once('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      row.getByRole('button', { name: /delete/i }).first().click(),
    ]);
    await page.goto(ADMIN.about);
    await expect(page.locator('body')).not.toContainText(title);
  });
});
