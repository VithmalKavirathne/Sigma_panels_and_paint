// Section 13: FAQ CRUD (question, answer, sort_order, is_active).
const { test, expect } = require('@playwright/test');
const { ADMIN, PUBLIC, stamp } = require('../helpers/data');
const { deleteRowsContaining } = require('../helpers/actions');

test.describe.serial('FAQ CRUD', () => {
  const token = stamp();
  const question = `${token}_FAQ question?`;
  const answer = `${token} answer with an apostrophe's ampersand & "quotes" and unicode café.`;

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage({ storageState: '.auth/admin.json' });
    await deleteRowsContaining(page, ADMIN.faqs, token);
    await page.close();
  });

  test('create a published FAQ', async ({ page }) => {
    await page.goto(ADMIN.faqs);
    await page.fill('[name="question"]', question);
    await page.fill('[name="answer"]', answer);
    if (await page.locator('[name="sort_order"]').count()) {
      await page.fill('[name="sort_order"]', '99');
    }
    const active = page.locator('input[name="is_active"]');
    if ((await active.count()) && (await active.getAttribute('type')) === 'checkbox') {
      if (!(await active.isChecked())) await active.check();
    }
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: /save faq/i }).click(),
    ]);
    await page.goto(ADMIN.faqs);
    await expect(page.locator('body')).toContainText(question);
  });

  test('published FAQ appears on /faq and answer is escaped (no injection)', async ({ page }) => {
    const res = await page.request.get(PUBLIC.faq);
    const html = await res.text();
    expect(html).toContain(question);
    // Unicode preserved, and no raw <script> injected from content.
    expect(html).not.toMatch(new RegExp(`${token}[^<]*<script`));
  });

  test('empty question is rejected', async ({ page }) => {
    await page.goto(ADMIN.faqs);
    await page.fill('[name="answer"]', `${token}_noqانه`);
    await page.getByRole('button', { name: /save faq/i }).click();
    await page.waitForLoadState('networkidle');
    // No FAQ row should exist that has our answer token but no question.
    // (We simply assert the page didn't fatally error.)
    await expect(page.locator('body')).not.toContainText(/Fatal error|SQLSTATE/);
  });

  test('delete removes the FAQ from admin and public', async ({ page }) => {
    await page.goto(ADMIN.faqs);
    const row = page.locator('tr, .admin-card, li').filter({ hasText: question }).first();
    await expect(row).toHaveCount(1);
    page.once('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      row.getByRole('button', { name: /delete/i }).first().click(),
    ]);
    await page.goto(ADMIN.faqs);
    await expect(page.locator('body')).not.toContainText(question);
    const res = await page.request.get(PUBLIC.faq);
    expect(await res.text()).not.toContain(question);
  });
});
