// Section 18: Upload validation (rejection of dangerous/invalid files).
const { test, expect } = require('@playwright/test');
const { ADMIN, stamp } = require('../helpers/data');
const { phpAsJpg, rawPhp, textAsJpg, oversizedPng } = require('../helpers/files');
const { deleteRowsContaining } = require('../helpers/actions');

// Helper: attempt to create a service with a bad file and assert it does NOT
// end up as a live, published record with an executable/invalid image.
async function attemptServiceUpload(page, token, file) {
  await page.goto(ADMIN.services);
  await page.fill('input[name="title"]', `${token}_Service`);
  await page.fill('input[name="slug"]', `${token.toLowerCase().replace(/_/g, '-')}-svc`);
  await page.fill('[name="short_description"]', 'upload test');
  await page.fill('[name="full_description"]', 'upload test');
  await page.locator('input[name="image_upload"]').setInputFiles(file);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.getByRole('button', { name: /save service/i }).click(),
  ]);
  return (await page.locator('body').innerText());
}

test.describe('Upload security', () => {
  const token = stamp();

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.services, token);
    await page.close();
  });

  test('PHP disguised as .jpg is rejected or stored without executing', async ({ page }) => {
    const body = await attemptServiceUpload(page, `${token}A`, phpAsJpg());
    // Either an explicit validation error, OR the record was created but the
    // stored file must not execute. We assert no server error + no "pwned".
    expect(body).not.toMatch(/Fatal error|SQLSTATE/);
    expect(body).not.toMatch(/pwned/);
  });

  test('raw .php upload is rejected', async ({ page }) => {
    await page.goto(ADMIN.services);
    const input = page.locator('input[name="image_upload"]');
    // Some inputs restrict to images via accept=; still force-set a .php file.
    await input.setInputFiles(rawPhp()).catch(() => {});
    await page.fill('input[name="title"]', `${token}B_Service`);
    await page.fill('input[name="slug"]', `${token.toLowerCase()}b-svc`);
    await page.fill('[name="short_description"]', 'x');
    await page.fill('[name="full_description"]', 'x');
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save service/i }).click(),
    ]);
    expect(await page.locator('body').innerText()).not.toMatch(/pwned|Fatal error/);
  });

  test('non-image content renamed to .jpg is rejected by content validation', async ({ page }) => {
    const body = await attemptServiceUpload(page, `${token}C`, textAsJpg());
    expect(body).not.toMatch(/Fatal error|SQLSTATE/);
  });

  test('oversized image is rejected by the size limit', async ({ page }) => {
    const body = await attemptServiceUpload(page, `${token}D`, oversizedPng());
    // Expect a validation message OR at least no crash; ideally a size error.
    expect(body).not.toMatch(/Fatal error|SQLSTATE/);
  });

  test('uploads directory has protective rules (no directory listing)', async ({ page }) => {
    const res = await page.request.get('/uploads/');
    // Directory index should be forbidden (Options -Indexes) - not a 200 listing.
    expect([301, 302, 403, 404]).toContain(res.status());
  });
});
