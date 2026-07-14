// Section 10: Public quote submission -> admin visibility, status, notes, delete.
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('Quote requests', () => {
  const token = stamp();
  const name = `${token}_Quote`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.quotes, token);
    await page.close();
  });

  test('submit the public quote form', async ({ page }) => {
    await page.goto(PUBLIC.quote);
    await page.fill('#customer_name', name);
    await page.fill('#phone', '0400000000');
    await page.fill('#email', 'test_admin_quote@example.com');
    // service_interest is a required <select>; pick the first real option.
    const svc = page.locator('#service_interest');
    if (await svc.count()) {
      const optionValues = await svc.locator('option').evaluateAll(
        (opts) => opts.map((o) => o.value).filter((v) => v)
      );
      if (optionValues.length) await svc.selectOption(optionValues[0]);
    }
    await page.fill('#project_location', 'TEST_ADMIN location QLD');
    await page.fill('#message', `${token} please quote a bumper respray. Unicode café & "quotes".`);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /send quote request/i }).click(),
    ]);
    // A success confirmation should appear (thank-you text or success alert).
    await expect(page.locator('body')).toContainText(/thank|received|success|submitted/i);
  });

  test('quote appears in admin with the submitted fields', async ({ page }) => {
    await page.goto(ADMIN.quotes);
    await expect(page.locator('body')).toContainText(name);
  });

  test('admin can open the quote detail and save an internal note that persists', async ({ page }) => {
    await page.goto(ADMIN.quotes);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: name }).first();
    const view = row.getByRole('link', { name: /view|open|detail/i });
    test.skip((await view.count()) === 0, 'no detail link found for quote row');
    await view.first().click();
    await page.waitForLoadState('networkidle');

    const note = page.locator('textarea[name*="note"], textarea[name*="internal"]').first();
    if (await note.count()) {
      const noteText = `${token} internal note with apostrophe's & unicode café`;
      await note.fill(noteText);
      const saveBtn = page.getByRole('button', { name: /save|update/i }).first();
      await Promise.all([page.waitForLoadState('networkidle'), saveBtn.click()]);
      await page.reload();
      await expect(page.locator('textarea[name*="note"], textarea[name*="internal"]').first())
        .toHaveValue(new RegExp(token));
    }
  });

  test('nonexistent quote id is handled safely (no SQL error / crash)', async ({ page }) => {
    const res = await page.request.get(`${ADMIN.quotes}?id=99999999`);
    expect(res.status()).toBeLessThan(500);
    const body = await res.text();
    expect(body).not.toMatch(/SQLSTATE|Fatal error|Uncaught/);
  });

  test('delete requires confirmation and removes the quote', async ({ page }) => {
    await page.goto(ADMIN.quotes);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: name }).first();
    await expect(row).toHaveCount(1);
    const del = row.getByRole('button', { name: /delete/i });
    test.skip((await del.count()) === 0, 'no delete control on quote list (may be on detail page)');
    page.once('dialog', (d) => d.accept());
    await Promise.all([page.waitForLoadState('networkidle'), del.first().click()]);
    await page.goto(ADMIN.quotes);
    await expect(page.locator('body')).not.toContainText(name);
  });
});
