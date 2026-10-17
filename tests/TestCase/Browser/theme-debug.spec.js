import { test, expect } from '@playwright/test';

test('theme tracing preserves rendered values, options, and accessible associations', async ({ page }) => {
  const snapshots = [];
  for (const url of ['/forms', '/forms?themeDebug=1']) {
    await page.goto(url);
    snapshots.push(await page.locator('form').evaluate(form => ({
      valid: form.checkValidity(),
      entries: [...new FormData(form)].filter(([name, value]) => !name.startsWith('_') && typeof value === 'string'),
      text: form.innerText,
      options: [...form.querySelectorAll('option')].map(option => [option.text, option.value, option.selected]),
      ids: [...form.querySelectorAll('[id]')].map(element => element.id),
      descriptions: [...form.querySelectorAll('[aria-describedby]')].every(input =>
        input.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id))),
    })));
  }
  expect(snapshots[1]).toEqual(snapshots[0]);
  expect(snapshots[1].valid).toBe(true);
  expect(snapshots[1].descriptions).toBe(true);
  expect(new Set(snapshots[1].ids).size).toBe(snapshots[1].ids.length);
  const comments = await page.evaluate(() => {
    const walker = document.createTreeWalker(document, NodeFilter.SHOW_COMMENT);
    const comments = [];
    while (walker.nextNode()) comments.push(walker.currentNode.data);
    return comments.join('\n');
  });
  expect(comments).toContain('element: forms/starter/control');
  expect(comments).toContain('element: FormCompose.form/base/select/native');
  expect(comments).toContain('field: comments.2.body');
  expect(comments).not.toContain('reader@example.test');
  expect(comments).not.toContain('Hello');
});

test('debug comments preserve a multipart POST through CSRF and form protection', async ({ page }) => {
  await page.goto('/forms?themeDebug=1');
  await page.locator('[name="attachment"]').setInputFiles({
    name: 'note.txt', mimeType: 'text/plain', buffer: Buffer.from('Debug mode upload'),
  });
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page).toHaveURL('/forms/save');
  const data = JSON.parse(await page.locator('body').innerText());
  expect(data).toMatchObject({ id: '42', email: 'reader@example.test', bio: 'Hello <world>' });
  expect(data.comments).toEqual({ 0: { id: '10', body: 'First' }, 2: { id: '12', body: 'Third' } });
  expect(data).not.toHaveProperty('_Token');
});

test('debug rendering retains locked hidden-field protection', async ({ page }) => {
  await page.goto('/forms?themeDebug=1');
  const status = await page.locator('form').evaluate(async form => {
    const data = new FormData(form);
    data.set('id', '99');
    return (await fetch(form.action, { method: 'POST', body: data })).status;
  });
  expect(status).toBe(400);
});
