import { test, expect } from '@playwright/test';

test('complete layouts place a finished summary before fields with working focus links', async ({ page }) => {
  await page.goto('/forms/complete');
  await expect(page.locator('form')).toHaveCount(1);
  await expect(page.locator('[data-form-layout]')).toContainText('Review the title');
  expect(await page.locator('form').evaluate(form => {
    const summary = form.querySelector('section');
    const body = form.querySelector('[data-form-body]');
    return Boolean(summary.compareDocumentPosition(body) & Node.DOCUMENT_POSITION_FOLLOWING);
  })).toBe(true);
  await page.getByRole('link', { name: 'Title: Review the title' }).click();
  await expect(page.getByLabel('Title', { exact: true })).toBeFocused();
  await expect(page.getByLabel('Title', { exact: true })).toHaveValue('A complete form');
  await expect(page.getByLabel('Default email', { exact: true })).toHaveValue('editor@example.test');
});

for (const [label, intent] of [['Check', 'check'], ['Save complete form', 'save']]) {
  test(`layout action ${label} submits through CSRF and form protection with an upload`, async ({ page }) => {
    await page.goto('/forms/complete');
    await page.getByLabel('Attachment').setInputFiles({
      name: 'layout.txt', mimeType: 'text/plain', buffer: Buffer.from('Layout submission'),
    });
    await page.getByRole('button', { name: label, exact: true }).click();
    await expect(page).toHaveURL('/forms/save');
    const data = JSON.parse(await page.locator('body').innerText());
    expect(data).toMatchObject({ id: '42', title: 'A complete form', email: 'editor@example.test', intent });
    expect(data).toHaveProperty('attachment');
    expect(data).not.toHaveProperty('_Token');
  });
}

test('complete layouts preserve locked hidden-field protection', async ({ page }) => {
  await page.goto('/forms/complete');
  const status = await page.locator('form').evaluate(async form => {
    const data = new FormData(form);
    data.set('id', '99');
    return (await fetch(form.action, { method: 'POST', body: data })).status;
  });
  expect(status).toBe(400);
});
