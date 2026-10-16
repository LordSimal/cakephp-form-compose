import { test, expect } from '@playwright/test';

test('native validity, successful controls, and theme associations', async ({ page }) => {
  await page.goto('/forms');
  const state = await page.locator('form').evaluate(form => ({
    valid: form.checkValidity(),
    entries: [...new FormData(form)].filter(([name, value]) => !name.startsWith('_') && typeof value === 'string'),
    checkboxRequired: [...form.querySelectorAll('input[type="checkbox"][name="topics[]"]')].map(input => input.required),
    ids: [...document.querySelectorAll('[id]')].map(element => element.id),
    descriptions: [...form.querySelectorAll('[aria-describedby]')].every(input =>
      input.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id))),
  }));
  expect(state.valid).toBe(true);
  expect(state.checkboxRequired).toEqual([false, false]);
  expect(state.ids.length).toBe(new Set(state.ids).size);
  expect(state.descriptions).toBe(true);
  expect(state.entries).toEqual([
    ['id', '42'], ['author_id', ''], ['topics', ''], ['topics[]', 'a'],
    ['active', '0'], ['visibility', ''], ['tags[_ids]', ''], ['email', 'reader@example.test'],
    ['author[name]', 'Ada'], ['bio', 'Hello <world>'],
  ]);
  await expect(page.locator('fieldset')).toHaveCount(2);
  await expect(page.locator('fieldset > legend')).toHaveCount(2);
  await expect(page.locator('[name="email"]')).toHaveAttribute('aria-describedby', 'email-hint email-feedback');
});

test('a real multipart browser POST passes Cake CSRF and form protection', async ({ page }) => {
  await page.goto('/forms');
  await page.locator('[name="attachment"]').setInputFiles({ name: 'note.txt', mimeType: 'text/plain', buffer: Buffer.from('Hello') });
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page).toHaveURL('/forms/save');
  const data = JSON.parse(await page.locator('body').innerText());
  expect(data).toMatchObject({ id: '42', author_id: '', topics: ['a'], active: '0', author: { name: 'Ada' } });
  expect(data).not.toHaveProperty('empty_select');
  expect(data).not.toHaveProperty('disabled');
  expect(data).not.toHaveProperty('_Token');
});

for (const attack of ['unexpected field', 'locked hidden value', 'missing CSRF']) {
  test(`Cake rejects ${attack}`, async ({ page }) => {
    await page.goto('/forms');
    const status = await page.locator('form').evaluate(async (form, attack) => {
      const data = new FormData(form);
      if (attack === 'unexpected field') data.set('injected', '1');
      if (attack === 'locked hidden value') data.set('id', '99');
      if (attack === 'missing CSRF') data.delete('_csrfToken');
      return (await fetch(form.action, { method: 'POST', body: data })).status;
    }, attack);
    expect(status).toBe(attack === 'missing CSRF' ? 403 : 400);
  });
}
