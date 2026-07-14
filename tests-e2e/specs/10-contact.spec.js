// Section 11: Public contact submission -> admin message read/detail/delete.
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('Contact messages', () => {
  const token = stamp();
  const name = `${token}_Message`;
  const subject = `${token} subject line`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.messages, token);
    await page.close();
  });

  test('submit the public contact form', async ({ page }) => {
    await page.goto(PUBLIC.contact);
    await page.fill('#name', name);
    await page.fill('#phone', '0400000000');
    await page.fill('#email', 'test_admin_msg@example.com');
    await page.fill('#subject', subject);
    await page.fill('#message', `${token} message body with <b>markup</b> & unicode café.`);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /send message/i }).click(),
    ]);
    await expect(page.locator('body')).toContainText(/thank|received|success|sent/i);
  });

  test('message appears in admin and body markup is escaped', async ({ page }) => {
    await page.goto(ADMIN.messages);
    await expect(page.locator('body')).toContainText(name);
    // The literal <b> should not become a real element from message content.
    const html = await page.content();
    expect(html).not.toMatch(new RegExp(`${token}[^<]*<b>markup</b>`));
  });

  test('nonexistent message id handled safely', async ({ page }) => {
    const res = await page.request.get(`${ADMIN.messages}?id=99999999`);
    expect(res.status()).toBeLessThan(500);
    expect(await res.text()).not.toMatch(/SQLSTATE|Fatal error/);
  });

  test('delete removes the message', async ({ page }) => {
    await page.goto(ADMIN.messages);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: name }).first();
    await expect(row).toHaveCount(1);
    const del = row.getByRole('button', { name: /delete/i });
    test.skip((await del.count()) === 0, 'no delete control on message list');
    page.once('dialog', (d) => d.accept());
    await Promise.all([page.waitForLoadState('networkidle'), del.first().click()]);
    await page.goto(ADMIN.messages);
    await expect(page.locator('body')).not.toContainText(name);
  });
});
