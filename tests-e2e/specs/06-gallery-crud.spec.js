// Section 9: Gallery CRUD (schema uses title, category, description, single image).
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { validPng } = require('../helpers/files');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('Gallery CRUD', () => {
  const token = stamp();
  const title = `${token}_Gallery`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.gallery, token);
    await page.close();
  });

  test('create a gallery item with a valid image', async ({ page }) => {
    await page.goto(ADMIN.gallery);
    await page.fill('input[name="title"]', title);
    if (await page.locator('[name="category"]').count()) {
      const cat = page.locator('[name="category"]');
      if ((await cat.evaluate((el) => el.tagName)) === 'SELECT') {
        await cat.selectOption({ index: 1 }).catch(() => {});
      } else {
        await cat.fill('TEST_ADMIN_cat');
      }
    }
    await page.fill('[name="description"]', 'TEST gallery description');
    await page.locator('input[name="image_upload"]').setInputFiles(validPng());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save item/i }).click(),
    ]);
    await page.goto(ADMIN.gallery);
    await expect(page.locator('body')).toContainText(title);
  });

  test('gallery item shows on the public gallery page', async ({ page }) => {
    const res = await page.request.get(PUBLIC.gallery);
    expect(res.status()).toBeLessThan(400);
    // Public gallery shows the image/caption; assert the title text is present.
    expect(await res.text()).toContain(title);
  });

  test('delete removes the gallery item (and does not error the page)', async ({ page }) => {
    await page.goto(ADMIN.gallery);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: title }).first();
    await expect(row).toHaveCount(1);
    page.once('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      row.getByRole('button', { name: /delete/i }).first().click(),
    ]);
    await page.goto(ADMIN.gallery);
    await expect(page.locator('body')).not.toContainText(title);
  });
});
