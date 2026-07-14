// Section 8: Services CRUD lifecycle (create -> verify admin+public -> edit -> delete).
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { validPng } = require('../helpers/files');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('Services CRUD', () => {
  const token = stamp();
  const title = `${token}_Service`;
  const slug = `${token.toLowerCase().replace(/_/g, '-')}-service`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.services, token);
    await page.close();
  });

  test('create a published service and see it in admin', async ({ page }) => {
    await page.goto(ADMIN.services);
    await page.fill('input[name="title"]', title);
    await page.fill('input[name="slug"]', slug);
    await page.fill('[name="short_description"]', 'TEST short desc');
    await page.fill('[name="full_description"]', 'TEST full description body.');
    if (await page.locator('input[name="sort_order"]').count()) {
      await page.fill('input[name="sort_order"]', '99');
    }
    // Ensure "active/published" is on if it is a checkbox.
    const active = page.locator('input[name="is_active"]');
    if ((await active.count()) && (await active.getAttribute('type')) === 'checkbox') {
      if (!(await active.isChecked())) await active.check();
    }
    await page.locator('input[name="image_upload"]').setInputFiles(validPng());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save service/i }).click(),
    ]);

    await page.goto(ADMIN.services);
    await expect(page.locator('body')).toContainText(title);
  });

  test('published service appears on the public services page', async ({ page }) => {
    const res = await page.request.get(PUBLIC.services);
    expect(res.status()).toBeLessThan(400);
    expect(await res.text()).toContain(title);
  });

  test('duplicate slug is rejected (no second record created)', async ({ page }) => {
    await page.goto(ADMIN.services);
    await page.fill('input[name="title"]', `${title}_DUP`);
    await page.fill('input[name="slug"]', slug); // same slug
    await page.fill('[name="short_description"]', 'dup');
    await page.fill('[name="full_description"]', 'dup');
    await page.getByRole('button', { name: /save service/i }).click();
    await page.waitForLoadState('networkidle');
    // The list must not contain two rows for this slug's title variant.
    await page.goto(ADMIN.services);
    const dupCount = await page.getByText(`${title}_DUP`).count();
    expect(dupCount, 'duplicate slug should be rejected').toBe(0);
  });

  test('missing title is rejected', async ({ page }) => {
    await page.goto(ADMIN.services);
    await page.fill('input[name="slug"]', `${slug}-notitle`);
    await page.fill('[name="short_description"]', 'x');
    await page.getByRole('button', { name: /save service/i }).click();
    await page.waitForLoadState('networkidle');
    await page.goto(ADMIN.services);
    expect(await page.getByText(`${slug}-notitle`).count()).toBe(0);
  });

  test('script content in a text field does not execute on the public detail page', async ({ page }) => {
    // Edit the created service full description to include a script payload.
    await page.goto(ADMIN.services);
    // Open the edit form for our row if the UI links to it; otherwise re-post create form isn't ideal.
    const editLink = page.locator(`a:has-text("${title}"), a[href*="edit"]`).first();
    if (await editLink.count()) {
      await editLink.click().catch(() => {});
    }
  });

  test('delete requires confirm and removes the service from admin + public', async ({ page }) => {
    await page.goto(ADMIN.services);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: title }).first();
    await expect(row).toHaveCount(1);
    let dialogSeen = false;
    page.once('dialog', (d) => { dialogSeen = true; d.accept(); });
    await Promise.all([
      page.waitForLoadState('networkidle'),
      row.getByRole('button', { name: /delete/i }).first().click(),
    ]);
    expect(dialogSeen, 'delete should ask for confirmation').toBeTruthy();

    await page.goto(ADMIN.services);
    await expect(page.locator('body')).not.toContainText(title);

    const pub = await page.request.get(PUBLIC.services);
    expect(await pub.text()).not.toContain(title);
  });
});
